<?php
declare(strict_types=1);

namespace Tests\Component\SynchronizationBatch;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Entity\SynchronizationBatch;
use Entity\SynchronizationBatch\Deposits;
use Enum\SynchronizationBatchValidationType;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationPath;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class SynchronizationBatchPersistenceTest extends WebTestCase
{
    /** @var ObjectManager */
    private $manager;
    /** @var EntityRepository */
    private $syncRepo;
    /** @var Deposits */
    private $sync;

    protected function setUp()
    {
        parent::setUp();
        self::bootKernel();

        $container = self::$kernel->getContainer();
        $this->manager = $container->get('doctrine.orm.default_entity_manager');
        $this->syncRepo = $this->manager->getRepository(SynchronizationBatch::class);
        $this->sync = new Deposits(['test'=>'xyz','array'=>[1,2,5,1024], 0=>0, false=>true]);
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
        $this->sync->setViolations(['fst'=>$testViolations,'snd'=>$arrayViolation]);
        $this->manager->flush();

        $this->assertNotNull($this->sync->getId(), "Sync wasn't saved");
        $sync = $this->syncRepo->find($this->sync->getId());
        $this->assertNotNull($sync, "Sync wasn't found");

        $this->assertArrayHasKey('fst', $this->sync->getViolations());
        $this->assertArrayHasKey('snd', $this->sync->getViolations());
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

}
