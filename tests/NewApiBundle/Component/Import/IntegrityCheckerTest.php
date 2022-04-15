<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use UserBundle\Entity\User;

class IntegrityCheckerTest extends KernelTestCase
{

    /** @var EntityManagerInterface */
    private static $entityManager;

    /** @var IntegrityChecker */
    private static $integrityChecker;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        self::$integrityChecker = $kernel->getContainer()->get(IntegrityChecker::class);
    }

    public function testParseEmpty()
    {
        $project = self::$entityManager->getRepository(Project::class)->findBy(['archived' => false, 'iso3' => 'KHM'], null, 1)[0];
        $user = self::$entityManager->getRepository(User::class)->findBy([], null, 1)[0];

        $import = new Import('KHM', 'test', null, [$project], $user);
        $file = new ImportFile('fake_file.xlsx', $import, $user);
        $item = new ImportQueue($import, $file, [[/** empty row */]]);
        self::$entityManager->persist($import);
        self::$entityManager->persist($file);
        self::$entityManager->persist($item);
        self::$entityManager->flush();

        $method = new \ReflectionMethod(self::$integrityChecker, 'checkOne');
        $method->setAccessible(true);
        $method->invoke(self::$integrityChecker, $item);

        $this->assertJson($item->getMessage());
    }

    public function testParseCorrect()
    {
        $project = self::$entityManager->getRepository(Project::class)->findBy(['archived' => false, 'iso3' => 'KHM'], null, 1)[0];
        $user = self::$entityManager->getRepository(User::class)->findBy([], null, 1)[0];

        $import = new Import('KHM', 'test', null, [$project], $user);
        $file = new ImportFile('fake_file.xlsx', $import, $user);
        $item = new ImportQueue($import, $file, json_decode(ImportFinishServiceTest::TEST_QUEUE_ITEM, true));
        self::$entityManager->persist($import);
        self::$entityManager->persist($file);
        self::$entityManager->persist($item);
        self::$entityManager->flush();

        $checker = self::$integrityChecker;

        $method = new \ReflectionMethod($checker, 'checkOne');
        $method->setAccessible(true);
        $method->invoke($checker, $item);

        $this->assertNull($item->getMessage());
    }

    public function testCheck()
    {
        $project = self::$entityManager->getRepository(Project::class)->findBy(['archived' => false, 'iso3' => 'KHM'], null, 1)[0];
        $user = self::$entityManager->getRepository(User::class)->findBy([], null, 1)[0];

        $import = new Import('KHM', 'test', null, [$project], $user);
        $import->setState(ImportState::INTEGRITY_CHECKING);

        $file = new ImportFile('fake_file.xlsx', $import, $user);
        $item = new ImportQueue($import, $file, json_decode(ImportFinishServiceTest::TEST_QUEUE_ITEM, true));
        self::$entityManager->persist($import);
        self::$entityManager->persist($file);
        self::$entityManager->persist($item);
        self::$entityManager->flush();

        $checker = self::$integrityChecker;
        $checker->check($import);

        $queue = self::$entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import], ['id' => 'asc']);
        $this->assertCount(1, $queue);
        foreach ($queue as $item) {
            $this->assertEquals(ImportQueueState::VALID, $item->getState(), "Queue is invalid because ".$item->getMessage());
        }
    }

    public function testAllDuplicitiesIdentified()
    {
        $project = self::$entityManager->getRepository(Project::class)->findBy(['archived' => false, 'iso3' => 'KHM'], null, 1)[0];
        $user = self::$entityManager->getRepository(User::class)->findBy([], null, 1)[0];

        $import = new Import('KHM', 'test', null, [$project], $user);
        $import->setState(ImportState::INTEGRITY_CHECKING);

        $file = new ImportFile('fake_file.xlsx', $import, $user);
        $itemA = new ImportQueue($import, $file, json_decode(ImportFinishServiceTest::TEST_QUEUE_ITEM, true));
        $itemB = new ImportQueue($import, $file, json_decode(ImportFinishServiceTest::TEST_QUEUE_ITEM, true));
        self::$entityManager->persist($import);
        self::$entityManager->persist($file);
        self::$entityManager->persist($itemA);
        self::$entityManager->persist($itemB);
        self::$entityManager->flush();

        $checker = self::$integrityChecker;
        $checker->check($import);

        $queue = self::$entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import], ['id' => 'asc']);
        $this->assertCount(2, $queue);
        foreach ($queue as $item) {
            $this->assertEquals(ImportQueueState::INVALID, $item->getState(), "Queue shouldn't be valid.");
        }
    }

    protected function tearDown()
    {
        parent::tearDown();
        self::$entityManager->clear();
    }

}
