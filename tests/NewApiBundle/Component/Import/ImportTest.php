<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use NewApiBundle\Entity\CountrySpecific;
use NewApiBundle\Entity\CountrySpecificAnswer;
use NewApiBundle\Entity\NationalId;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use NewApiBundle\Component\Import\ImportFileValidator;
use NewApiBundle\Component\Import\Integrity\DuplicityService;
use NewApiBundle\Entity\ImportHouseholdDuplicity;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\HouseholdAssets;
use NewApiBundle\Enum\HouseholdShelterStatus;
use NewApiBundle\Enum\HouseholdSupportReceivedType;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\Enum\PersonGender;
use NewApiBundle\InputType\Import\Duplicity\ResolveSingleDuplicityInputType;
use ProjectBundle\Enum\Livelihood;
use ProjectBundle\Utils\ProjectService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
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

        $this->uploadService = $kernel->getContainer()->get(UploadImportService::class);
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
            'zero width space' => ['KHM', 'zero-width.xlsx', 2, 2, 2],
            'minimal csv without IDs' => ['KHM', 'KHM-Import-2HH-3HHM-55HHM-no-dupl.csv', 2, 60, 0],
            'minimal csv' => ['KHM', 'KHM-Import-2HH-3HHM-55HHM.csv', 2, 60, 1],
            'minimal ods' => ['KHM', 'KHM-Import-2HH-3HHM-24HHM.ods', 2, 29, 2],
            'minimal xlsx' => ['KHM', 'KHM-Import-4HH-0HHM-0HHM.xlsx', 4, 4, 4],
            'camp only' => ['SYR', 'SYR-only-camp-1HH.xlsx', 1, 7, 1],
            'excel date format' => ['KHM', 'KHM-Import-1HH-0HHM-0HHM-excel-date-format.xlsx', 1, 1, 1],
            // takes too long, only for local testing
            // 'very big import' => ['SYR', 'SYR-Import-500HH-0HHM.xlsx', 500, 500, 0],
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

        $this->userStartedUploading($import, true);

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
    public function testMinimalWorkflowWithoutSimilarityCheck(string $country, string $filename, int $expectedHouseholdCount, int $expectedBeneficiaryCount)
    {
        $this->project = $this->createBlankProject($country, [__METHOD__, $filename]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport("testMinimalWorkflow", $this->project, $filename);

        $this->userStartedUploading($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedIntegrityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedIdentityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->assertQueueCount($expectedHouseholdCount, $import, [ImportQueueState::TO_CREATE]);

        $this->userStartedFinishing($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->getImported($import);
        $this->assertCount($expectedBeneficiaryCount, $bnfCount, "Wrong beneficiary count");
    }

    public function integrityFixedFiles(): array
    {
        return [
            'full replacement of all wrong households' => [
                'SYR',
                'SYR-WrongDatedImport-5HH.xlsx',
                'SYR-only-camp-1HH.xlsx',
                1,
                7
            ],
            'reupload fixed households' => [
                'KHM',
                'import_3duplicity_first_run.ods',
                'import_3duplicityFixed_second_run.ods',
                4,
                4
            ],
        ];
    }

    /**
     * @dataProvider integrityFixedFiles
     *
     * @param string $country
     * @param string $integrityWrongFile
     * @param string $fixedFile
     * @param int    $expectedHouseholdCount
     * @param int    $expectedBeneficiaryCount
     */
    public function testFixIntegrityErrors(string $country, string $integrityWrongFile, string $fixedFile, int $expectedHouseholdCount, int $expectedBeneficiaryCount)
    {
        $this->project = $this->createBlankProject($country, [__METHOD__, $integrityWrongFile]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport("testFixIntegrityErrors", $this->project, $integrityWrongFile);

        $this->userStartedUploading($import, true);

        $this->userStartedIntegrityCheck($import, false);

        $this->uploadFile($import, $fixedFile);

        $this->userStartedUploading($import, true);

        $this->userStartedIntegrityCheck($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import, [ImportQueueState::VALID]);

        $this->userStartedIdentityCheck($import, true);
        $this->userStartedSimilarityCheck($import, true);
        $this->userStartedFinishing($import);

        $this->assertQueueCount($expectedHouseholdCount, $import, [ImportQueueState::CREATED]);
        $importedBnfs = $this->entityManager->getRepository(Beneficiary::class)->getImported($import);
        $this->assertCount($expectedBeneficiaryCount, $importedBnfs, "Wrong beneficiary count");
    }

    public function testCountrySpecifics()
    {
        $country = 'SYR';
        $filename = 'import-demo-3-country-specifics.xlsx';
        $expectedHouseholdCount = 1;
        $expectedBeneficiaryCount = 11;

        // prepare country specifics
        $customLocationSpecific = $this->entityManager->getRepository(CountrySpecific::class)->findOneBy(['fieldString'=>'Custom Location', 'countryIso3'=>$country]);
        if (!$customLocationSpecific) {
            $customLocationSpecific = new CountrySpecific('Custom Location', 'text', $country);
            $this->entityManager->persist($customLocationSpecific);
            $this->entityManager->flush();
        }

        $this->project = $this->createBlankProject($country, [__METHOD__, $filename]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport("testCountrySpecifics", $this->project, $filename);

        $this->userStartedUploading($import, true);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedIntegrityCheck($import, true, $this->getBatchCount($import, 'integrity_check'));

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedIdentityCheck($import, true, $this->getBatchCount($import, 'identity_check'));

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $this->userStartedSimilarityCheck($import, true, $this->getBatchCount($import, 'similarity_check'));

        $this->assertQueueCount($expectedHouseholdCount, $import);
        $this->assertQueueCount($expectedHouseholdCount, $import, [ImportQueueState::TO_CREATE]);

        $this->userStartedFinishing($import);

        $this->assertQueueCount($expectedHouseholdCount, $import);

        $importedBnfs = $this->entityManager->getRepository(Beneficiary::class)->getImported($import);
        $this->assertCount($expectedBeneficiaryCount, $importedBnfs, "Wrong beneficiary count");

        foreach ($importedBnfs as $bnf) {
            /** @var Household $hh */
            $hh = $bnf->getHousehold();
            $this->entityManager->refresh($hh);

            $answers = $hh->getCountrySpecificAnswers();
            $this->assertCount(1, $answers);

            /** @var CountrySpecificAnswer $answer */
            $answer = $answers[0];
            $this->assertEquals('Custom Location', $answer->getCountrySpecific()->getFieldString());
            $this->assertEquals('TEST-DEMO-1', $answer->getAnswer());
        }
    }

    public function testEnumCaseSensitivity()
    {
        foreach ($this->entityManager->getRepository(NationalId::class)->findBy(['idNumber'=>[
            '98300834', '124483434', '102', '789465432654', '789', '456', '8798798', '345456',
        ]]) as $idCard) {
            $this->entityManager->remove($idCard);
        }
        $this->entityManager->flush();

        $filename = 'KHM-Import-insensitive-2HH-3HHM-24HHM.ods';
        $this->project = $this->createBlankProject('KHM', [__METHOD__, $filename]);
        $this->originHousehold = $this->createBlankHousehold($this->project);
        $import = $this->createImport("testMinimalWorkflow", $this->project, $filename);

        $this->userStartedUploading($import, true);
        $this->userStartedIntegrityCheck($import, true);
        $this->userStartedIdentityCheck($import, true);
        $this->userStartedSimilarityCheck($import, true);
        $this->userStartedFinishing($import);

        $this->assertQueueCount(2, $import, [ImportQueueState::CREATED]);
        /** @var BeneficiaryRepository $bnfRepo */
        $bnfRepo = $this->entityManager->getRepository(Beneficiary::class);
        $bnfs = $bnfRepo->getImported($import);
        $this->assertCount(32, $bnfs, "Wrong beneficiary count");

        // John Smith
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '98300834');
        $this->assertIsHead($bnf);
        $this->assertHHHasAssets($bnf->getHousehold(), [HouseholdAssets::AC, HouseholdAssets::CAR]);
        $this->assertHHHasSupportTypes($bnf->getHousehold(), [HouseholdSupportReceivedType::MPCA]);
        $this->assertHHHasShelterStatus($bnf->getHousehold(), HouseholdShelterStatus::TENT);
        $this->assertHHHasLivelihood($bnf->getHousehold(), Livelihood::REGULAR_SALARY_PUBLIC);
        $this->assertEquals(PersonGender::MALE, $bnf->getPerson()->getGender());
        // Homer Simpson
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '124483434');
        $this->assertIsHead($bnf);
        $this->assertHHHasAssets($bnf->getHousehold(), [HouseholdAssets::MOTORBIKE, HouseholdAssets::WASHING_MACHINE]);
        $this->assertHHHasSupportTypes($bnf->getHousehold(), [HouseholdSupportReceivedType::FOOD_KIT, HouseholdSupportReceivedType::LIVELIHOODS_SUPPORT]);
        $this->assertHHHasShelterStatus($bnf->getHousehold(), HouseholdShelterStatus::HOUSE_APARTMENT_LIGHTLY_DAMAGED);
        $this->assertHHHasLivelihood($bnf->getHousehold(), Livelihood::FARMING_LIVESTOCK);
        $this->assertEquals(PersonGender::MALE, $bnf->getPerson()->getGender());
        // Marge simpson
        $bnf = $this->findOneIdentity(NationalIdType::FAMILY, '102');
        $this->assertIsMember($bnf);
        $this->assertEquals(PersonGender::FEMALE, $bnf->getPerson()->getGender());
        // Bart Simpson
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '789465432654');
        $this->assertIsMember($bnf);
        $this->assertEquals(PersonGender::MALE, $bnf->getPerson()->getGender());
        // Lisa Simpson
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '789');
        $this->assertIsMember($bnf);
        $this->assertEquals(PersonGender::FEMALE, $bnf->getPerson()->getGender());
        // Maggie	Simpson
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '456');
        $this->assertIsMember($bnf);
        $this->assertEquals(PersonGender::FEMALE, $bnf->getPerson()->getGender());
        // Abraham	Simpson
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '8798798');
        $this->assertIsMember($bnf);
        $this->assertEquals(PersonGender::MALE, $bnf->getPerson()->getGender());
        // Mona	Simpson
        $bnf = $this->findOneIdentity(NationalIdType::NATIONAL_ID, '345456');
        $this->assertIsMember($bnf);
        $this->assertEquals(PersonGender::FEMALE, $bnf->getPerson()->getGender());
    }

    private function assertIsMember(Beneficiary $beneficiary): void
    {
        $this->assertEquals(false, $beneficiary->isHead(), "Beneficiary {$beneficiary->getLocalGivenName()} {$beneficiary->getLocalFamilyName()} shouldn't be head.");
    }

    private function assertIsHead(Beneficiary $beneficiary): void
    {
        $this->assertEquals(true, $beneficiary->isHead(), "Beneficiary {$beneficiary->getLocalGivenName()} {$beneficiary->getLocalFamilyName()} should be head.");
    }

    private function assertHHHasShelterStatus(Household $household, string $expectedType): void
    {
        $this->assertEquals($expectedType, $household->getShelterStatus(), "Shelter status doesn't fit for HH ".$household->getHouseholdHead()->getLocalFamilyName()." [{$household->getShelterStatus()}]");
    }

    private function assertHHHasLivelihood(Household $household, string $expectedLivelihood): void
    {
        $this->assertEquals($expectedLivelihood, $household->getLivelihood(), "Livelihood doesn't fit for HH ".$household->getHouseholdHead()->getLocalFamilyName()." [{$household->getLivelihood()}]");
    }

    private function assertHHHasSupportTypes(Household $household, array $expectedTypes): void
    {
        $this->assertCount(count($expectedTypes), $household->getSupportReceivedTypes(), "Support types count doesn't fit for HH ".$household->getHouseholdHead()->getLocalFamilyName());
        $supportTypes = implode(', ', $household->getSupportReceivedTypes());
        foreach ($expectedTypes as $expectedAsset) {
            $this->assertContains($expectedAsset, $household->getSupportReceivedTypes(), "Support types doesn't fit for HH ".$household->getHouseholdHead()->getLocalFamilyName()." [$supportTypes]");
        }
    }

    private function assertHHHasAssets(Household $household, array $expectedAssets): void
    {
        $this->assertCount(count($expectedAssets), $household->getAssets(), "Asset count doesn't fit for HH ".$household->getHouseholdHead()->getLocalFamilyName());
        $hhAssets = implode(', ', $household->getAssets());
        foreach ($expectedAssets as $expectedAsset) {
            $this->assertContains($expectedAsset, $household->getAssets(), "Assets doesn't fit for HH ".$household->getHouseholdHead()->getLocalFamilyName()." [$hhAssets]");
        }
    }

    private function findOneIdentity(string $idType, string $idNumber): Beneficiary
    {
        /** @var BeneficiaryRepository $bnfRepo */
        $bnfRepo = $this->entityManager->getRepository(Beneficiary::class);
        $identities = $bnfRepo->findIdentity($idType, $idNumber);
        $this->assertCount(1, $identities, "There are ID conflict for $idType with $idNumber");
        return $identities[0];
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

            $this->userStartedUploading($import, true);
            $this->userStartedIntegrityCheck($import, true, $this->getBatchCount($import, 'integrity_check'));
            $this->userStartedIdentityCheck($import, true, $this->getBatchCount($import, 'identity_check'));
            $this->userStartedSimilarityCheck($import, true, $this->getBatchCount($import, 'similarity_check'));

            $imports[$runName] = $import;
        }

        // finish first
        $import = $imports['first'];

        $this->userStartedFinishing($import);

        $import = $imports['second'];
        $this->entityManager->refresh($import);

        if ($expectedDuplicities === 0) {
            $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
            return; // another check doesn't have any meaning
        } else {
            $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $import->getState());
        }

        $stats = $this->importService->getStatistics($import);
        $this->assertEquals($expectedDuplicities, $stats->getAmountIdentityDuplicities());

        // resolve all as duplicity to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $import, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);
        /** @var ImportQueue $item */
        foreach ($queue as $item) {
            $this->assertGreaterThan(0, count($item->getHouseholdDuplicities()));
            /** @var ImportHouseholdDuplicity $firstDuplicity */
            $firstDuplicity = $item->getHouseholdDuplicities()->first();

            $duplicityResolve = new ResolveSingleDuplicityInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($firstDuplicity->getTheirs()->getId());
            $this->importService->resolveDuplicity($item, $duplicityResolve, $this->getUser());
        }

        $this->assertQueueCount(0, $import, [ImportQueueState::IDENTITY_CANDIDATE]);
        $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());

        $this->userStartedSimilarityCheck($import, true, $this->getBatchCount($import, 'similarity_check'));

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

            $this->userStartedUploading($import, true);
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

        $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $secondImport->getState());

        $firstImportBeneficiary = $firstImport->getImportBeneficiaries()[0]->getBeneficiary();
        $this->assertEquals(1, $firstImport->getImportBeneficiaries()->count());
        $this->assertEquals('John', $firstImportBeneficiary->getPerson()->getLocalGivenName());

        $this->assertQueueCount(1, $firstImport, [ImportQueueState::CREATED]);
        $this->assertQueueCount(1, $secondImport, [ImportQueueState::IDENTITY_CANDIDATE]);

        // resolve all as duplicity on second import to update and continue
        $queue = $this->entityManager->getRepository(ImportQueue::class)->findBy(['import' => $secondImport, 'state' => ImportQueueState::IDENTITY_CANDIDATE], ['id' => 'asc']);

        /** @var ImportQueue $item */
        foreach ($queue as $item) {
            $this->assertGreaterThan(0, count($item->getHouseholdDuplicities()));
            /** @var ImportHouseholdDuplicity $firstDuplicity */
            $firstDuplicity = $item->getHouseholdDuplicities()->first();

            $duplicityResolve = new ResolveSingleDuplicityInputType();
            $duplicityResolve->setStatus(ImportQueueState::TO_UPDATE);
            $duplicityResolve->setAcceptedDuplicityId($firstDuplicity->getTheirs()->getId());
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
        $this->userStartedUploading($import, true);
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
        $project->setIso3('ARM');
        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $import = $this->createImport('testWrongCountryIntegrityCheck', $project, $filename);

        $this->userStartedUploading($import, true);
        $this->userStartedIntegrityCheck($import, false, $this->getBatchCount($import, 'integrity_check'));

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

        $this->uploadFile($import, $fileName);

        $this->userStartedUploading($import, true);
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
        $this->uploadService->uploadFile($import, $file, $this->getUser());
    }

    /**
     * @deprecated
     * @param Import $import
     * @param        $phase
     *
     * @return int
     */
    private function getBatchCount(Import $import, $phase): int
    {
        return 100;
    }
}
