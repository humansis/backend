<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

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
use NewApiBundle\InputType\ImportUpdateStatusInputType;
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


        $this->project = new Project();
        $this->project->setName(uniqid());
        $this->project->setStartDate(new \DateTime());
        $this->project->setEndDate(new \DateTime());
        $this->project->setIso3(self::TEST_COUNTRY);
        $this->entityManager->persist($this->project);
        $this->entityManager->flush();

        $this->originHousehold = $this->createBlankHousehold($this->project);
    }

    public function correctFiles(): array
    {
        return [
            'minimal ods' => ['Import.ods'],
            'minimal xlsx' => ['CorrectImport.xlsx'],
        ];
    }

    /**
     * @dataProvider correctFiles
     */
    public function testMinimalWorkflow($filename)
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
		$fs->copy(__DIR__.'/../../Resources/'.$filename, $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, 'Import.ods', null, null, true);
        $importFile = $this->uploadService->uploadFile($import, $file, $this->getUser());
        $this->uploadService->load($importFile);

        $this->assertNotNull($importFile->getId(), "ImportFile wasn't saved to DB");

        // start integrity check
        $userStartedIntegrityCheck = new ImportUpdateStatusInputType();
        $userStartedIntegrityCheck->setStatus(ImportState::INTEGRITY_CHECKING);
        $this->importService->updateStatus($import, $userStartedIntegrityCheck);

        $this->assertEquals(ImportState::INTEGRITY_CHECKING, $import->getState());

        $checkIntegrityCommand = $this->application->find('app:import:integrity');
        $commandTester = new CommandTester($checkIntegrityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:integrity failed");

        // start identity check
        $userStartedIdentityCheck = new ImportUpdateStatusInputType();
        $userStartedIdentityCheck->setStatus(ImportState::IDENTITY_CHECKING);
        $this->importService->updateStatus($import, $userStartedIdentityCheck);

        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());

        $checkIdentityCommand = $this->application->find('app:import:identity');
        $commandTester = new CommandTester($checkIdentityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:identity failed");

        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());

        // start similarity check
        $userStartedSimilarityCheck = new ImportUpdateStatusInputType();
        $userStartedSimilarityCheck->setStatus(ImportState::SIMILARITY_CHECKING);
        $this->importService->updateStatus($import, $userStartedSimilarityCheck);

        $this->assertEquals(ImportState::SIMILARITY_CHECKING, $import->getState());

        $checkSimilarityCommand = $this->application->find('app:import:similarity');
        $commandTester = new CommandTester($checkSimilarityCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:similarity failed");

        $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());

        // save to DB
        $userStartedSimilarityCheck = new ImportUpdateStatusInputType();
        $userStartedSimilarityCheck->setStatus(ImportState::IMPORTING);
        $this->importService->updateStatus($import, $userStartedSimilarityCheck);

        $this->assertEquals(ImportState::IMPORTING, $import->getState());

        $finishCommand = $this->application->find('app:import:finish');
        $commandTester = new CommandTester($finishCommand);
        $commandTester->execute([
            'import' => $import->getId(),
        ]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command app:import:finish failed");

        $this->assertEquals(ImportState::FINISHED, $import->getState());
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
		$fs->copy(__DIR__.'/../../Resources/ImportWithWrongDateFormat.xlsx', $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, 'ImportWithWrongDateFormat.xlsx', null, null, true);
        $importFile = $this->uploadService->uploadFile($import, $file, $this->getUser());
        $this->uploadService->load($importFile);

        $this->assertNotNull($importFile->getId(), "ImportFile wasn't saved to DB");

        // start integrity check
        $userStartedIntegrityCheck = new ImportUpdateStatusInputType();
        $userStartedIntegrityCheck->setStatus(ImportState::INTEGRITY_CHECKING);
        $this->importService->updateStatus($import, $userStartedIntegrityCheck);

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
