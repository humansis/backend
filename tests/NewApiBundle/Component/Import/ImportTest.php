<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use NewApiBundle\Component\Import\ImportFileValidator;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\InputType\DuplicityResolveInputType;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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

        $this->importService = $kernel->getContainer()->get(ImportService::class);

        $this->uploadService = new UploadImportService(
            $this->entityManager,
            $kernel->getContainer()->getParameter('import.uploadedFilesDirectory'),
            $kernel->getContainer()->get(ImportFileValidator::class)
        );
        $this->projectService = $kernel->getContainer()->get('project.project_service');

        foreach ($this->entityManager->getRepository(Import::class)->findAll() as $import) {
            $this->entityManager->remove($import);
            foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($import) as $bnf) {
                if ($bnf->getHousehold()) {
                    $kernel->getContainer()->get('beneficiary.household_service')->remove($bnf->getHousehold());
                }
                $kernel->getContainer()->get('beneficiary.beneficiary_service')->remove($bnf);
            }
        }

    }

    public function correctFiles(): array
    {
        return [ // ISO3-filename, HH count, BNF count, duplicity in reimport
            'minimal csv' => ['KHM', 'KHM-Import-2HH-3HHM-55HHM.csv', 2, 60, 1],
            'minimal ods' => ['KHM', 'KHM-Import-2HH-3HHM-24HHM.ods', 2, 29, 2],
            'minimal xlsx' => ['KHM', 'KHM-Import-4HH-0HHM-0HHM.xlsx', 4, 4, 4],
            'camp only' => ['SYR', 'SYR-only-camp-1HH.xlsx', 1, 7, 1],
            'excel date format' => ['KHM', 'KHM-Import-1HH-0HHM-0HHM-excel-date-format.xlsx', 1, 1, 1],
        ];
    }

    public function incorrectFiles(): array
    {
        return [
            'missing mandatory columns' => ['import_missing_simple_mandatory_columns.ods'],
        ];
    }

    /**
     * @dataProvider correctFiles
     */
    public function testMinimalWorkflow(string $country, string $filename, int $expectedHouseholdCount, int $expectedBeneficiaryCount)
    {
        $this->project = $this->createBlankProject($country, [__METHOD__, $filename]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport("testMinimalWorkflow", $this->project, $filename);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedIntegrityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedIdentityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedSimilarityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);
        $this->assertQueueCount($expectedHouseholdCount, $import, [ImportQueueState::TO_CREATE]);

        $this->userStartedFinishing($import);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->getImported($import);
        $this->assertCount($expectedBeneficiaryCount, $bnfCount, "Wrong beneficiary count");
    }

    /**
     * @dataProvider correctFiles
     */
    public function testRepeatedUploadSameFile(string $country, string $filename, int $expectedHouseholdCount, int $expectedBeneficiaryCount, int $expectedDuplicities)
    {
        $this->project = $this->createBlankProject($country, [__METHOD__, $filename]);
        $this->originHousehold = $this->createBlankHousehold($this->project);

        $imports = [];
        foreach (['first', 'second'] as $runName) {
            $import = $this->createImport("testRepeatedUploadSameFile[$runName]", $this->project, $filename);

            $this->userStartedIntegrityCheck($import, true);
            $this->userStartedIdentityCheck($import, true);
            $this->userStartedSimilarityCheck($import, true);

            $imports[$runName] = $import;
        }

        // finish first
        $import = $imports['first'];

        $this->userStartedFinishing($import);

        $import = $imports['second'];

        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());
        $this->cli('app:import:identity', $import);
        $this->entityManager->refresh($import);

        $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $import->getState());

        $stats = $this->importService->getStatistics($import);
        $this->assertEquals($expectedDuplicities, $stats->getAmountDuplicities());

        // resolve all as duplicity to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
        foreach ($queue as $item) {
            $duplicityResolve = new DuplicityResolveInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($item->getDuplicities()[0]->getId());
            $this->importService->resolveDuplicity($item, $duplicityResolve, $this->getUser());
        }

        $count = $this->entityManager->getRepository(ImportQueue::class)->count(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE]);
        $this->assertEquals(0, $count, "Some duplicities wasn't resolved");
        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());

        $this->userStartedSimilarityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);
        $this->assertQueueCount($expectedHouseholdCount-$expectedDuplicities, $import, [ImportQueueState::TO_CREATE]);
        $this->assertQueueCount($expectedDuplicities, $import, [ImportQueueState::TO_UPDATE]);
        $this->assertQueueCount(0, $import, [ImportQueueState::TO_LINK]);

        // save to DB
        $this->userStartedFinishing($import);

        $beneficiaryIds = [];
        $householdIds = [];
        foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($imports['first']) as $beneficiary) {
            $householdIds[] = $beneficiary->getHousehold()->getId();
            $beneficiaryIds[] = $beneficiary->getId();
        }
        foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($imports['second']) as $beneficiary) {
            $householdIds[] = $beneficiary->getHousehold()->getId();
            $beneficiaryIds[] = $beneficiary->getId();
        }
        $this->assertCount($expectedHouseholdCount*2 - $expectedDuplicities, array_unique($householdIds), "Some duplicities was saved instead of updated");
        // dont know how many bnf shold be in database
        // $this->assertCount($expectedBeneficiaryCount, array_unique($beneficiaryIds), "Some duplicities was saved instead of updated");
    }

    public function testUpdateSimpleDuplicity()
    {
        $this->project = $this->createBlankProject(self::TEST_COUNTRY, [__METHOD__]);
        $this->originHousehold = $this->createBlankHousehold($this->project);

        $testFiles = [
            'first' => 'import_update_household_first_run.ods',
            'second' => 'import_update_household_second_run.ods',
        ];

        $imports = [];
        foreach (['first', 'second'] as $runName) {
            $import = $this->createImport("testUpdateSimpleDuplicity[$runName]", $this->project, $testFiles[$runName]);

            $this->userStartedIntegrityCheck($import, true);
            $this->userStartedIdentityCheck($import, true);
            $this->userStartedSimilarityCheck($import, true);

            $imports[$runName] = $import;
        }

        // finish first
        $firstImport = $imports['first'];
        $this->userStartedFinishing($firstImport);

        $this->entityManager->refresh($firstImport);

        $firstImportBeneficiary = $firstImport->getImportBeneficiaries()[0]->getBeneficiary();
        $this->assertEquals(1, $firstImport->getImportBeneficiaries()->count());
        $this->assertEquals('John', $firstImportBeneficiary->getPerson()->getLocalGivenName());

        //check identity again on second import
        $secondImport = $imports['second'];

        $this->cli('app:import:identity', $secondImport);
        $this->entityManager->refresh($secondImport);

        // resolve all as duplicity on second import to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $secondImport, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
        foreach ($queue as $item) {
            $duplicityResolve = new DuplicityResolveInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($item->getDuplicities()[0]->getId());
            $this->importService->resolveDuplicity($item, $duplicityResolve, $this->getUser());
        }

        // start similarity check on second import
        $this->userStartedSimilarityCheck($secondImport, true);

        // finish second import
        $this->userStartedFinishing($secondImport);

        $secondImportBeneficiary = $secondImport->getImportBeneficiaries()[0]->getBeneficiary();
        $this->assertEquals(1, $secondImport->getImportBeneficiaries()->count());
        $this->assertEquals('William', $secondImportBeneficiary->getPerson()->getLocalGivenName());

        // test, if beneficiary was really updated and not created
        $this->assertEquals($firstImportBeneficiary->getId(), $secondImportBeneficiary->getId());
        $this->assertEquals(1, $secondImportBeneficiary->getHousehold()->getBeneficiaries()->count());
    }

    public function testErrorInIntegrityCheck()
    {
        $this->project = $this->createBlankProject(self::TEST_COUNTRY, [__METHOD__]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport('testErrorInIntegrityCheck', $this->project, 'KHM-WrongDateImport-2HH-3HHM.csv');

        // start integrity check
        $this->userStartedIntegrityCheck($import, false);
    }

    /**
     * @dataProvider correctFiles
     */
    public function testWrongCountryIntegrityCheck(string $country, string $filename)
    {
        $this->project = $this->createBlankProject($country, [__METHOD__, $filename]);
        $this->originHousehold = $this->createBlankHousehold($this->project);

        // SYR project
        $project = new Project();
        $project->setName(uniqid());
        $project->setNotes(get_class($this));
        $project->setStartDate(new \DateTime());
        $project->setEndDate(new \DateTime());
        $project->setIso3('QTI');
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $import = $this->createImport('testWrongCountryIntegrityCheck', $project, $filename);

        $this->userStartedIntegrityCheck($import, false);

        $this->cli('app:import:clean', $import);
    }

    /**
     * @dataProvider incorrectFiles
     * @param string $fileName
     */
    public function testIncorrectImportFileInIntegrityCheck(string $fileName): void
    {
        $this->project = $this->createBlankProject(self::TEST_COUNTRY, [__METHOD__, $fileName]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport('testIncorrectImportFileInIntegrityCheck', $this->project);

        try {
            $this->uploadFile($import, $fileName);
            $this->fail('Upload of incorrect file should throw exception');
        } catch (\InvalidArgumentException $exception) {
            // it is expected
        }

        $this->userStartedIntegrityCheck($import, false);
        $this->assertQueueCount(0, $import);
    }

    private function userStartedIntegrityCheck(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);
        $this->assertEquals(ImportState::INTEGRITY_CHECKING, $import->getState());
        $this->cli('app:import:integrity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::INTEGRITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::INTEGRITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedIdentityCheck(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, ImportState::IDENTITY_CHECKING);
        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());
        $this->cli('app:import:identity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedSimilarityCheck(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, ImportState::SIMILARITY_CHECKING);
        $this->assertEquals(ImportState::SIMILARITY_CHECKING, $import->getState());
        $this->cli('app:import:similarity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedFinishing(Import $import): void
    {
        $this->importService->updateStatus($import, ImportState::IMPORTING);
        $this->assertEquals(ImportState::SIMILARITY_CHECKING, $import->getState());
        $this->cli('app:import:finish', $import);
        $this->assertEquals(ImportState::FINISHED, $import->getState());
    }

    private function cli(string $commandName, Import $import): void
    {
        $command = $this->application->find($commandName);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['import' => $import->getId()]);
        $commandTester->execute(['import' => $import->getId()]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command $commandName failed");
    }

    private function assertQueueCount(int $expectedCount, Import $import, ?array $filterQueueStates = null): void
    {
        if ($filterQueueStates === null) {
            $queueCount = $this->entityManager->getRepository(ImportQueue::class)->count(['import' => $import]);
            $this->assertEquals($expectedCount, $queueCount, 'There should be other amount of queue items');
        } else {
            $queue = $this->entityManager->getRepository(ImportQueue::class)->count([
                'import' => $import,
                'state' => $filterQueueStates
            ]);
            $this->assertCount($expectedCount, $queue);
        }
    }

    private function createBlankProject(string $country, array $notes): Project
    {
        $project = new Project();
        $project->setName(uniqid());
        $project->setNotes(implode("\n", $notes));
        $project->setStartDate(new \DateTime());
        $project->setEndDate(new \DateTime());
        $project->setIso3($country);
        $this->entityManager->persist($project);
        $this->entityManager->flush();
        return $project;
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

        $nationalId = new NationalId();
        $nationalId->setIdType('National ID');
        $nationalId->setIdNumber('123456789');
        $hhh->getPerson()->addNationalId($nationalId);
        $nationalId->setPerson($hhh->getPerson());

        $hh->addBeneficiary($hhh);
        $hh->addProject($project);
        $hhh->addProject($project);
        $this->entityManager->persist($nationalId);
        $this->entityManager->persist($hh);
        $this->entityManager->persist($hhh);
        $this->entityManager->flush();
        return $hh;
    }

    private function getUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy([], ['id' => 'asc']);
    }

    private function createImport(string $name, Project $project, ?string $fileName = null): Import
    {
        $createImportInput = new ImportCreateInputType();
        $createImportInput->setTitle($name);
        $createImportInput->setDescription(__METHOD__);
        $createImportInput->setProjectId($project->getId());
        $import = $this->importService->create($createImportInput, $this->getUser());

        $this->assertNotNull($import->getId(), "Import wasn't saved to DB");
        $this->assertEquals(ImportState::NEW, $import->getState());

        if ($fileName) {
            $this->uploadFile($import, $fileName);
        }

        return $import;
    }

    private function uploadFile(Import $import, string $filename): void
    {
        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__.'/../../Resources/'.$filename, $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, $filename, null, null, true);
        $importFile = $this->uploadService->uploadFile($import, $file, $this->getUser());
        $this->uploadService->load($importFile);

        $this->assertNotNull($importFile->getId(), "ImportFile wasn't saved to DB");
    }
}
