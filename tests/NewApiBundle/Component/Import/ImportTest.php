<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use NewApiBundle\Component\Import\ImportFileValidator;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\PersonGender;
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
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\NewApiBundle\Component\Import\Helper\ChecksTrait;
use Tests\NewApiBundle\Component\Import\Helper\CliTrait;
use Tests\NewApiBundle\Component\Import\Helper\DefaultDataTrait;

class ImportTest extends KernelTestCase
{
    use CliTrait;
    use ChecksTrait;
    use DefaultDataTrait;

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
            'error from stage' => ['SYR', 'import27.xlsx', 1, 1, 1],
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

        $this->userStartedIdentityCheck($import, false);

        $stats = $this->importService->getStatistics($import);
        $this->assertEquals($expectedDuplicities, $stats->getAmountDuplicities());

        // resolve all as duplicity to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
        foreach ($queue as $item) {
            $this->assertGreaterThan(0, count($item->getDuplicities()));
            $firstDuplicity = $item->getDuplicities()->first();

            $duplicityResolve = new DuplicityResolveInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($firstDuplicity->getId());
            $this->importService->resolveDuplicity($item, $duplicityResolve, $this->getUser());
        }

        $this->assertQueueCount(0, $import, [ImportQueueState::IDENTITY_CANDIDATE]);
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
        $secondImport = $imports['second'];

        $this->assertQueueCount(1, $firstImport, [ImportQueueState::TO_CREATE]);
        $this->assertQueueCount(1, $secondImport, [ImportQueueState::TO_CREATE]);

        $this->userStartedFinishing($firstImport);

        $this->assertQueueCount(1, $firstImport, [ImportQueueState::CREATED]);
        $this->assertQueueCount(1, $secondImport, [ImportQueueState::VALID]);

        $this->entityManager->refresh($firstImport);
        $this->entityManager->refresh($secondImport);
        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());

        $firstImportBeneficiary = $firstImport->getImportBeneficiaries()[0]->getBeneficiary();
        $this->assertEquals(1, $firstImport->getImportBeneficiaries()->count());
        $this->assertEquals('John', $firstImportBeneficiary->getPerson()->getLocalGivenName());

        //check identity again on second import
        $this->userStartedIdentityCheck($import, false);
        $this->entityManager->refresh($secondImport);

        $this->assertQueueCount(1, $firstImport, [ImportQueueState::CREATED]);
        $this->assertQueueCount(1, $secondImport, [ImportQueueState::IDENTITY_CANDIDATE]);

        // resolve all as duplicity on second import to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $secondImport, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
        foreach ($queue as $item) {
            $this->assertGreaterThan(0, count($item->getDuplicities()));
            $firstDuplicity = $item->getDuplicities()->first();

            $duplicityResolve = new DuplicityResolveInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($firstDuplicity->getId());
            $this->importService->resolveDuplicity($item, $duplicityResolve, $this->getUser());
        }
        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());

        $this->assertQueueCount(1, $firstImport, [ImportQueueState::CREATED]);
        $this->assertQueueCount(1, $secondImport, [ImportQueueState::TO_UPDATE]);

        // start similarity check on second import
        $this->userStartedSimilarityCheck($secondImport, true);

        // finish second import
        $this->userStartedFinishing($secondImport);

        $this->assertQueueCount(1, $firstImport, [ImportQueueState::CREATED]);
        $this->assertQueueCount(1, $secondImport, [ImportQueueState::UPDATED]);

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
