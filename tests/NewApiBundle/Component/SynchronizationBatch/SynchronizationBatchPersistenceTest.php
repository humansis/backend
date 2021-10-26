<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\SynchronizationBatch;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use NewApiBundle\Entity\SynchronizationBatch;
use NewApiBundle\Entity\SynchronizationBatch\Deposits;
use NewApiBundle\Enum\SynchronizationBatchValidationType;
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
        $this->sync = new SynchronizationBatch(['test'=>'xyz','array'=>[1,2,5,1024], 0=>0, false=>true], SynchronizationBatchValidationType::PURCHASE);
        $this->manager->persist($this->sync);
    }

    public function testDepositPersistence()
    {
        $testViolations = new ConstraintViolationList();
        $testViolations->add(new ConstraintViolation("Test is wrong", null, [], null, 'test', 'xyz'));
        $arrayViolation = new ConstraintViolationList();
        $arrayViolation->add(new ConstraintViolation("5th array is wrong", null, [], null, 'array[3]', '5'));
        $this->sync->setViolations(['test'=>serialize($testViolations),'array'=>[5=>serialize($arrayViolation)]]);
        $this->manager->flush();

        $this->assertNotNull($this->sync->getId(), "Sync wasn't saved");
        $sync = $this->syncRepo->find($this->sync->getId());
        $this->assertNotNull($sync, "Sync wasn't found");
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

}
