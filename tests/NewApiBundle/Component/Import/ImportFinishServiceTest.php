<?php

namespace Tests\NewApiBundle\Component\Import;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UserBundle\Entity\User;

class ImportFinishServiceTest extends KernelTestCase
{
    const TEST_COUNTRY = 'KHM';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImportService */
    private $importService;

    /** @var Project */
    private $project;

    /** @var Import */
    private $import;

    /** @var ImportFile */
    private $importFile;

    protected function setUp()
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $testUser = $this->entityManager->getRepository(User::class)->findOneBy([]);

        $this->importService = new ImportService($this->entityManager);

        $this->project = new Project();
        $this->project->setName(uniqid());
        $this->project->setStartDate(new \DateTime());
        $this->project->setEndDate(new \DateTime());
        $this->project->setIso3(self::TEST_COUNTRY);
        $this->entityManager->persist($this->project);
        $this->entityManager->flush();

        $this->createBlankHousehold($this->project);

        $this->import = new Import('unit test', 'note', $this->project,$testUser);
        $this->import->setState(ImportState::SIMILARITY_CHECK_CORRECT);
        $this->entityManager->persist($this->import);

        $this->importFile = new ImportFile('unit-test.xlsx', $this->import, $testUser);
        $this->entityManager->persist($this->importFile);
    }

    public function testCreate()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, '');
        $queueItem->setState(ImportQueueState::TO_CREATE);
        $this->entityManager->flush();

        $this->importService->finish($this->import);

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(2, $bnfCount, "Wrong number of created beneficiaries");
    }

    public function testUpdate()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, '');
        $queueItem->setState(ImportQueueState::TO_UPDATE);
        $this->entityManager->flush();

        $this->importService->finish($this->import);

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");
    }

    public function testLink()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, '');
        $queueItem->setState(ImportQueueState::TO_LINK);
        $this->entityManager->flush();

        $this->importService->finish($this->import);

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");
    }

    public function testIgnore()
    {
        $queueItem = new ImportQueue($this->import, $this->importFile, '');
        $queueItem->setState(ImportQueueState::TO_IGNORE);
        $this->entityManager->flush();

        $this->importService->finish($this->import);

        $bnfCount = $this->entityManager->getRepository(Beneficiary::class)->countAllInProject($this->project);
        $this->assertEquals(1, $bnfCount, "Wrong number of created beneficiaries");
    }

    protected function tearDown()
    {
        $this->assertEquals(ImportState::FINISHED, $this->import->getState(), "Wrong import state");
        $queueSize = $this->entityManager->getRepository(ImportQueue::class)->countBy([
            'import' => $this->import,
        ]);
        $this->assertEquals(0, $queueSize, "Queue wasn't cleaned");
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

}
