<?php

declare(strict_types=1);

namespace Tests\Component\SynchronizationBatch;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Entity\SynchronizationBatch;
use Entity\SynchronizationBatch\Deposits;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class SynchronizationBatchPersistenceTest extends WebTestCase
{
    private EntityManagerInterface $manager;

    private EntityRepository $syncRepo;

    private Deposits $sync;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $container = self::getContainer();
        $this->manager = $container->get('doctrine.orm.default_entity_manager');
        $this->syncRepo = $this->manager->getRepository(SynchronizationBatch::class);
        $this->sync = new Deposits(['test' => 'xyz', 'array' => [1, 2, 5, 1024], 0 => 0, false => true]);
        $this->manager->persist($this->sync);
    }

    public function testDepositPersistence()
    {
        $testViolations = new ConstraintViolationList();
        $testViolations->add(new ConstraintViolation("Test is wrong", null, [], null, 'test', 'xyz'));
        $testViolations->add(new ConstraintViolation("Test is somewhat weird", null, [], null, 'test', 'xyz'));
        $testViolations->add(new ConstraintViolation("Test2 should be longer", null, [], null, 'test2', 'xyz'));
        $arrayViolation = new ConstraintViolationList();
        $arrayViolation->add(new ConstraintViolation("5th array is wrong", null, [], null, 'array[3]', '5'));
        $this->sync->setViolations(['fst' => $testViolations, 'snd' => $arrayViolation]);
        $this->manager->flush();

        $this->assertNotNull($this->sync->getId(), "Sync wasn't saved");
        $sync = $this->syncRepo->find($this->sync->getId());
        $this->assertNotNull($sync, "Sync wasn't found");

        $this->assertArrayHasKey('fst', $this->sync->getViolations());
        $this->assertArrayHasKey('snd', $this->sync->getViolations());
    }
}
