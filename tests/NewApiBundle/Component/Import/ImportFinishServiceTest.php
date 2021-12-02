<?php

namespace Tests\NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportBeneficiary;
use NewApiBundle\Entity\ImportBeneficiaryDuplicity;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\ImportPatchInputType;
use ProjectBundle\Entity\Project;
use ProjectBundle\Utils\ProjectService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UserBundle\Entity\User;

class ImportFinishServiceTest extends KernelTestCase
{
    const TEST_COUNTRY = 'KHM';
    // json copied from KHM-Import-2HH-3HHM.ods
    const TEST_QUEUE_ITEM = '[
    {
        "Adm1": "Banteay Meanchey",
        "Adm2": null, 
        "Adm3": null, 
        "Adm4": null, 
        "Head": "true",
        "ID Number": 123456789, 
        "ID Type": "National ID",
        "F 0 - 2": 1, 
        "F 2 - 5": 2, 
        "F 6 - 17": 3, 
        "F 18 - 59": 4, 
        "F 60+": 5,
        "M 0 - 2": null, 
        "M 2 - 5": null, 
        "M 6 - 17": null, 
        "M 18 - 59": null, 
        "M 60+": null,
        "Notes": "import from unittest", 
        "Assets": null, 
        "Gender": "Male",  
        "Latitude": null, 
        "Camp name": null, 
        "Longitude": null, 
        "Debt Level": 3, 
        "Livelihood": "Government", 
        "Tent number": null, 
        "Income level": null, 
        "Type phone 1": "Mobile", 
        "Type phone 2": null, 
        "Date of birth": "31-12-2000", 
        "Proxy phone 1": null, 
        "Proxy phone 2": null, 
        "Address number": 123, 
        "Address street": "Fake St", 
        "Number phone 1": "10834243", 
        "Number phone 2": null, 
        "Prefix phone 1": "+855", 
        "Prefix phone 2": null, 
        "Shelter status": null, 
        "Address postcode": 90210, 
        "Local given name": "John", 
        "Residency status": "Resident", 
        "Local family name": "Smith", 
        "English given name": null, 
        "English family name": null, 
        "Food Consumption Score": 3, 
        "Support Received Types": "MPCA", 
        "Vulnerability criteria": "disabled", 
        "Coping Strategies Index": 2
    }
    ]';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImportService */
    private $importService;

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

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->importService = $kernel->getContainer()->get(ImportService::class);
        $this->projectService = $kernel->getContainer()->get('project.project_service');

        // clean all import
        foreach ($this->entityManager->getRepository(Import::class)->findAll() as $import) {
            $this->entityManager->remove($import);
            foreach ($this->entityManager->getRepository(Beneficiary::class)->getImported($import) as $bnf) {
                if ($bnf->getHousehold()) {
                    $kernel->getContainer()->get('beneficiary.household_service')->remove($bnf->getHousehold());
                }
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

        $this->import = new Import('unit test', 'note', $this->project, $this->getUser());
        $this->import->setState(ImportState::SIMILARITY_CHECK_CORRECT);
        $this->entityManager->persist($this->import);

        $this->importFile = new ImportFile('unit-test.xlsx', $this->import, $this->getUser());
        $this->importFile->setIsLoaded(true);

        $this->entityManager->persist($this->importFile);
        $this->entityManager->flush();
    }

    public function testEmpty()
    {
        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertEmpty($originLinks, "Origin beneficiary shouldn't have any import link");

        $links = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'import' => $this->import->getId()
        ]);
        $this->assertCount(0, $links, "There should be no link");
    }

    public function testPlainCreate()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, json_decode(self::TEST_QUEUE_ITEM, true));
        $queueItem->setState(ImportQueueState::TO_CREATE);
        $this->entityManager->persist($queueItem);
        $this->entityManager->flush();

        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(17, $bnfCount, "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertEmpty($originLinks, "Origin beneficiary shouldn't have any import link");

        $links = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'import' => $this->import->getId()
        ]);
        $this->assertCount(16, $links, "There should be only one link");
    }

    public function testDecidedCreate()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, json_decode(self::TEST_QUEUE_ITEM, true));
        $queueItem->setState(ImportQueueState::TO_CREATE);
        $duplicity = new ImportBeneficiaryDuplicity($queueItem, $this->originHousehold);
        $duplicity->setState(ImportDuplicityState::NO_DUPLICITY);
        $duplicity->setDecideAt(new \DateTime());
        $duplicity->setDecideBy($this->getUser());
        $queueItem->getDuplicities()->add($duplicity);
        $this->entityManager->persist($queueItem);
        $this->entityManager->persist($duplicity);
        $this->entityManager->flush();

        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(17, $bnfCount, "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertEmpty($originLinks, "Origin beneficiary shouldn't have any import link");

        $links = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'import' => $this->import->getId()
        ]);
        $this->assertCount(16, $links, "There should be only one link");
    }

    public function testUpdate()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, json_decode(self::TEST_QUEUE_ITEM, true));
        $queueItem->setState(ImportQueueState::TO_UPDATE);
        $duplicity = new ImportBeneficiaryDuplicity($queueItem, $this->originHousehold);
        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_OURS);
        $duplicity->setDecideAt(new \DateTime());
        $duplicity->setDecideBy($this->getUser());
        $queueItem->getDuplicities()->add($duplicity);
        $this->entityManager->persist($queueItem);
        $this->entityManager->persist($duplicity);
        $this->entityManager->flush();

        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $links = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'import' => $this->import->getId()
        ]);
        $this->assertEquals(16, count($links), "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertCount(1, $originLinks, "Origin beneficiary should have one import link");
    }

    public function testLink()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, json_decode(self::TEST_QUEUE_ITEM, true));
        $queueItem->setState(ImportQueueState::TO_LINK);
        $duplicity = new ImportBeneficiaryDuplicity($queueItem, $this->originHousehold);
        $duplicity->setState(ImportDuplicityState::DUPLICITY_KEEP_THEIRS);
        $duplicity->setDecideAt(new \DateTime());
        $duplicity->setDecideBy($this->getUser());
        $queueItem->getDuplicities()->add($duplicity);
        $this->entityManager->persist($queueItem);
        $this->entityManager->persist($duplicity);
        $this->entityManager->flush();

        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertCount(1, $originLinks, "Origin beneficiary should have one import link");
    }

    public function testIgnore()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, json_decode(self::TEST_QUEUE_ITEM, true));
        $queueItem->setState(ImportQueueState::TO_IGNORE);
        $this->entityManager->persist($queueItem);
        $this->entityManager->flush();

        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertEmpty($originLinks, "Origin beneficiary shouldn't have any import link");
    }

    public function testUndecided()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, json_decode(self::TEST_QUEUE_ITEM, true));
        $queueItem->setState(ImportQueueState::IDENTITY_CANDIDATE);
        $duplicity = new ImportBeneficiaryDuplicity($queueItem, $this->originHousehold);
        $duplicity->setState(ImportDuplicityState::DUPLICITY_CANDIDATE);
        $duplicity->setDecideAt(new \DateTime());
        $duplicity->setDecideBy($this->getUser());
        $queueItem->getDuplicities()->add($duplicity);
        $this->entityManager->persist($queueItem);
        $this->entityManager->persist($duplicity);
        $this->entityManager->flush();

        $this->importService->patch($this->import, new ImportPatchInputType(ImportState::IMPORTING));

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");

        $originLinks = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'beneficiary' => $this->originHousehold->getHouseholdHead()->getId()
        ]);
        $this->assertEmpty($originLinks, "Origin beneficiary shouldn't have any import link");

        $links = $this->entityManager->getRepository(ImportBeneficiary::class)->findBy([
            'import' => $this->import->getId()
        ]);
        $this->assertCount(0, $links, "There should be no link");
    }

    protected function tearDown()
    {
        $this->assertEquals(ImportState::FINISHED, $this->import->getState(), "Wrong import state");
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
        return $this->entityManager->getRepository(User::class)->findOneBy([]);
    }

}
