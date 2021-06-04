<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use NewApiBundle\Enum\ImportQueueState;
use ProjectBundle\Utils\ProjectService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\ImportCreateInputType;
use NewApiBundle\InputType\ImportPatchInputType;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserBundle\Entity\User;

class ImportTest extends KernelTestCase
{
    const TEST_COUNTRY = 'KHM';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Application */
    private $application;

    /** @var ImportService */
    private $importService;

    /** @var UploadImportService */
    private $uploadService;

    /** @var Project */
    private $project;

    /** @var Import */
    private $import;

    /** @var Household */
    private $originHousehold;

    /** @var ImportFile */
    private $importFile;
    /** @var ProjectService */
    private $projectService;

    protected function setUp()
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->application = new Application($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->importService = new ImportService(
            $this->entityManager,
            $kernel->getContainer()->get('beneficiary.household_service'),
            $kernel->getContainer()->get('monolog.logger.import')
        );

        $this->uploadService = new UploadImportService(
            $this->entityManager,
            $kernel->getContainer()->getParameter('import.uploadedFilesDirectory'),
        );
        $this->projectService = $kernel->getContainer()->get('project.project_service');

        foreach ($this->entityManager->getRepository(Import::class)->findAll() as $import) {
            $this->entityManager->remove($import);
            foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($import) as $bnf) {
                $kernel->getContainer()->get('beneficiary.household_service')->remove($bnf->getHousehold());
                $kernel->getContainer()->get('beneficiary.beneficiary_service')->remove($bnf);
            }
        }

        $this->project = new Project();
        $this->project->setName(uniqid());
        $this->project->setNotes(get_class($this));
        $this->project->setStartDate(new \DateTime());
        $this->project->setEndDate(new \DateTime());
        $this->project->setIso3(self::TEST_COUNTRY);
        $this->entityManager->persist($this->project);
        $this->entityManager->flush();

        $this->originHousehold = $this->createBlankHousehold($this->project);
    }

    public function correctFiles(): array
    {
        return [ // filename, HH count, duplicity in reimport
            'minimal csv' => ['KHM-Import-2HH-3HHM.csv', 2, 1],
            'minimal ods' => ['KHM-Import-2HH-3HHM.ods', 2, 2],
            'minimal xlsx' => ['KHM-Import-4HH-0HHM.xlsx', 4, 4],
        ];
    }

    /**
     * @dataProvider correctFiles
     */
    public function testMinimalWorkflow(string $filename, int $householdCount)
    {
        // create import
        $createImportInput = new ImportCreateInputType();
        $createImportInput->setTitle('unit test');
        $createImportInput->setDescription(__METHOD__);
        $createImportInput->setProjectId($this->project->getId());
        $import = $this->importService->create($createImportInput, $this->getUser());

        $this->assertNotNull($import->getId(), "Import wasn't saved to DB");
        $this->assertEquals(ImportState::NEW, $import->getState());

        // add file into import
        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__.'/../../Resources/'.$filename, $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, 'Import.ods', null, null, true);
        $importFile = $this->uploadService->uploadFile($import, $file, $this->getUser());
        $this->uploadService->load($importFile);

        $this->assertNotNull($importFile->getId(), "ImportFile wasn't saved to DB");
        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import]);
        $this->assertCount($householdCount, $queue);

        // start integrity check
        $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);

        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import]);
        $this->assertCount($householdCount, $queue);
        $this->assertEquals(ImportState::INTEGRITY_CHECKING, $import->getState());

        $checkIntegrityCommand = $this->application->find('app:import:integrity');
        $commandTester = new CommandTester($checkIntegrityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:integrity failed");

        $this->assertCount($householdCount, $queue);
        $this->assertEquals(ImportState::INTEGRITY_CHECK_CORRECT, $import->getState());

        // start identity check
        $this->importService->updateStatus($import, ImportState::IDENTITY_CHECKING);

        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());

        $checkIdentityCommand = $this->application->find('app:import:identity');
        $commandTester = new CommandTester($checkIdentityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:identity failed");

        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import]);
        $this->assertCount($householdCount, $queue);

        // start similarity check
        $this->importService->updateStatus($import, ImportState::SIMILARITY_CHECKING);

        $this->assertEquals(ImportState::SIMILARITY_CHECKING, $import->getState());

        $checkSimilarityCommand = $this->application->find('app:import:similarity');
        $commandTester = new CommandTester($checkSimilarityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:similarity failed");

        $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());
        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import]);
        $this->assertCount($householdCount, $queue);

        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import, 'state' => ImportQueueState::TO_CREATE]);
        $this->assertCount($householdCount, $queue);

        // save to DB
        $this->importService->updateStatus($import, ImportState::IMPORTING);

        $this->assertEquals(ImportState::IMPORTING, $import->getState());

        $finishCommand = $this->application->find('app:import:finish');
        $commandTester = new CommandTester($finishCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:finish failed");

        $this->assertEquals(ImportState::FINISHED, $import->getState());

        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import]);
        $this->assertCount($householdCount, $queue);
    }

    /**
     * @dataProvider correctFiles
     */
    public function testRepeatedUploadSameFile(string $filename, int $householdCount, int $expectedDuplicities)
    {
        $imports = [];
        foreach (['first', 'second'] as $runName) {
            // create import
            $createImportInput = new ImportCreateInputType();
            $createImportInput->setTitle($runName.' call of unit test');
            $createImportInput->setDescription(__METHOD__);
            $createImportInput->setProjectId($this->project->getId());
            $import = $this->importService->create($createImportInput, $this->getUser());

            $this->assertNotNull($import->getId(), "Import wasn't saved to DB");
            $this->assertEquals(ImportState::NEW, $import->getState());

            // add file into import
            $file = new UploadedFile(__DIR__.'/../../Resources/'.$filename, $filename);
            $importFile = $this->uploadService->upload($import, $file, $this->getUser());

            // start integrity check
            $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);

            $checkIntegrityCommand = $this->application->find('app:import:integrity');
            (new CommandTester($checkIntegrityCommand))->execute(['import' => $import->getId()]);

            // start identity check
            $this->importService->updateStatus($import, ImportState::IDENTITY_CHECKING);

            $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());

            $checkIdentityCommand = $this->application->find('app:import:identity');
            (new CommandTester($checkIdentityCommand))->execute(['import' => $import->getId()]);

            $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());

            // start similarity check
            $this->importService->updateStatus($import,ImportState::SIMILARITY_CHECKING);

            $this->assertEquals(ImportState::SIMILARITY_CHECKING, $import->getState());

            $checkSimilarityCommand = $this->application->find('app:import:similarity');
            (new CommandTester($checkSimilarityCommand))->execute(['import' => $import->getId()]);

            $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());

            $imports[$runName] = $import;
        }

        // finish first
        $import = $imports['first'];
        // save to DB
        $this->importService->updateStatus($import,ImportState::IMPORTING);

        $this->assertEquals(ImportState::IMPORTING, $import->getState());

        $finishCommand = $this->application->find('app:import:finish');
        (new CommandTester($finishCommand))->execute(['import' => $import->getId()]);

        $this->assertEquals(ImportState::FINISHED, $import->getState());

        $import = $imports['second'];

        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());

        $checkIdentityCommand = $this->application->find('app:import:identity');
        (new CommandTester($checkIdentityCommand))->execute(['import' => $import->getId()]);
        $this->entityManager->refresh($import);

        $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $import->getState());

        $stats = $this->importService->getStatistics($import);
        $this->assertEquals($expectedDuplicities, $stats->getAmountDuplicities());
    }

    public function testErrorInIntegrityCheck()
    {
        // create import
        $createImportInput = new ImportCreateInputType();
        $createImportInput->setTitle('unit test '.__CLASS__);
        $createImportInput->setDescription('unit test description '.__METHOD__);
        $createImportInput->setProjectId($this->project->getId());
        $import = $this->importService->create($createImportInput, $this->getUser());

        $this->assertNotNull($import->getId(), "Import wasn't saved to DB");
        $this->assertEquals(ImportState::NEW, $import->getState());

        // add file into import
        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__.'/../../Resources/KHM-WrongDateImport-2HH-3HHM.csv', $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, 'KHM-WrongDateImport-2HH-3HHM.csv', null, null, true);
        $importFile = $this->uploadService->uploadFile($import, $file, $this->getUser());
        $this->uploadService->load($importFile);

        $this->assertNotNull($importFile->getId(), "ImportFile wasn't saved to DB");

        // start integrity check
        $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);

        $this->assertEquals(ImportState::INTEGRITY_CHECKING, $import->getState());

        $checkIntegrityCommand = $this->application->find('app:import:integrity');
        $commandTester = new CommandTester($checkIntegrityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:integrity failed");
        $this->assertEquals(ImportState::INTEGRITY_CHECK_FAILED, $import->getState());
    }

    /**
     * @dataProvider correctFiles
     */
    public function testWrongCountryIntegrityCheck(string $filename)
    {
        // SYR project
        $project = new Project();
        $project->setName(uniqid());
        $project->setNotes(get_class($this));
        $project->setStartDate(new \DateTime());
        $project->setEndDate(new \DateTime());
        $project->setIso3('SYR');
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        // create import
        $createImportInput = new ImportCreateInputType();
        $createImportInput->setTitle('integrity failed unit test');
        $createImportInput->setDescription('KHM into SYR '.__METHOD__);
        $createImportInput->setProjectId($project->getId());
        $import = $this->importService->create($createImportInput, $this->getUser());

        $this->assertNotNull($import->getId(), "Import wasn't saved to DB");
        $this->assertEquals(ImportState::NEW, $import->getState());

        // add file into import
        $file = new UploadedFile(__DIR__.'/../../Resources/'.$filename, $filename);
        $importFile = $this->uploadService->upload($import, $file, $this->getUser());

        $this->assertNotNull($importFile->getId(), "ImportFile wasn't saved to DB");

        // start integrity check
        $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);

        $this->assertEquals(ImportState::INTEGRITY_CHECKING, $import->getState());

        $checkIntegrityCommand = $this->application->find('app:import:integrity');
        $commandTester = new CommandTester($checkIntegrityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:integrity failed");
        $this->assertEquals(ImportState::INTEGRITY_CHECK_FAILED, $import->getState());
    }

    private function createBlankHousehold(Project $project): Household
    {
        $hh = new Household();

        $hh->setLongitude('empty');
        $hh->setLatitude('empty');
        $hh->setCopingStrategiesIndex(0);
        $hh->setDebtLevel(0);
        $hh->setFoodConsumptionScore(0);
        $hh->setIncomeLevel(0);
        $hh->setNotes('default HH in '.__CLASS__);

        $hhh = new Beneficiary();
        $hhh->setHousehold($hh);
        $birthDate = new \DateTime();
        $birthDate->modify("-30 year");
        $hhh->getPerson()->setDateOfBirth($birthDate);
        $hhh->getPerson()->setEnFamilyName('empty');
        $hhh->getPerson()->setEnGivenName('empty');
        $hhh->getPerson()->setLocalFamilyName('empty');
        $hhh->getPerson()->setLocalGivenName('empty');
        $hhh->getPerson()->setGender(0);
        $hhh->setHead(true);
        $hhh->setResidencyStatus('empty');

        $hh->addBeneficiary($hhh);
        $hh->addProject($project);
        $hhh->addProject($project);
        $this->entityManager->persist($hh);
        $this->entityManager->persist($hhh);
        $this->entityManager->flush();
        return $hh;
    }

    private function getUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([]);
    }
}
