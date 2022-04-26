<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\ImportParser;
use NewApiBundle\Component\Import\ImportTemplate;
use NewApiBundle\Component\Import\IntegrityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Enum\ImportState;
use ProjectBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;
use UserBundle\Entity\User;

class IntegrityCheckerTest extends KernelTestCase
{

    /** @var EntityManagerInterface */
    private static $entityManager;

    /** @var IntegrityChecker */
    private static $integrityChecker;

    /** @var ImportInvalidFileService */
    private static $importInvalidFileService;

    /** @var string */
    private static $invalidFilesDirectory;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        self::$integrityChecker = $kernel->getContainer()->get(IntegrityChecker::class);
        self::$importInvalidFileService = $kernel->getContainer()->get(ImportInvalidFileService::class);
        self::$invalidFilesDirectory = $kernel->getContainer()->getParameter('import.invalidFilesDirectory');
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

    public function testValidationMessages()
    {
        $project = self::$entityManager->getRepository(Project::class)->findBy(['archived' => false, 'iso3' => 'KHM'], null, 1)[0];
        $user = self::$entityManager->getRepository(User::class)->findBy([], null, 1)[0];

        $import = new Import('KHM', 'test', null, [$project], $user);
        $file = new ImportFile('fake_file.xlsx', $import, $user);
        $correctItem = new ImportQueue($import, $file, json_decode(ImportFinishServiceTest::TEST_QUEUE_ITEM, true));
        $incorrectItem = new ImportQueue($import, $file, json_decode(ImportFinishServiceTest::TEST_WRONG_QUEUE_ITEM, true));
        self::$entityManager->persist($import);
        self::$entityManager->persist($file);
        self::$entityManager->persist($correctItem);
        self::$entityManager->persist($incorrectItem);
        self::$entityManager->flush();
        self::$entityManager->refresh($correctItem);
        self::$entityManager->refresh($incorrectItem);
        self::$entityManager->refresh($import);

        $checker = self::$integrityChecker;

        $method = new \ReflectionMethod($checker, 'checkOne');
        $method->setAccessible(true);
        $method->invoke($checker, $correctItem);
        $method->invoke($checker, $incorrectItem);

        $this->assertEquals(ImportQueueState::VALID, $correctItem->getState(), "Correct item should be recognize as one");
        $this->assertNull($correctItem->getMessage());
        $this->assertEquals(ImportQueueState::INVALID, $incorrectItem->getState(), "Incorrect item should be recognize as one");
        $this->assertNotNull($incorrectItem->getMessage());

        $invalidFilePath = self::$importInvalidFileService->generateFile($import);
        $invalidFile = new File(self::$invalidFilesDirectory.'/'.$invalidFilePath->getFilename());
        $parser = new ImportParser();

        $headers = $parser->parseHeadersOnly($invalidFile);
        $this->assertContains(ImportTemplate::ROW_NAME_STATUS, $headers);
        $this->assertContains(ImportTemplate::ROW_NAME_MESSAGES, $headers);

        $householdsData = $parser->parse($invalidFile);
        $this->assertCount(1, $householdsData);
        $beneficiariesData = $householdsData[0];
        $this->assertCount(1, $beneficiariesData);
        $this->assertEquals('ERROR', $beneficiariesData[0][ImportTemplate::ROW_NAME_STATUS]['value']);
        $this->assertGreaterThan(0, count(explode("\n", $beneficiariesData[0][ImportTemplate::ROW_NAME_MESSAGES]['value'])));
    }

    protected function tearDown()
    {
        parent::tearDown();
        self::$entityManager->clear();
    }

}
