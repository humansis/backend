<?php

declare(strict_types=1);

namespace Tests\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use Component\Import\ImportFileValidator;
use Component\Import\Integrity\DuplicityService;
use Component\Import\UploadImportService;
use Entity\Import;
use Entity\ImportQueue;
use Entity\Project;
use Entity\User;
use Enum\ImportQueueState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadImportServiceTest extends KernelTestCase
{
    /** @var UploadImportService */
    private $uploadService;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->uploadService = self::getContainer()->get(UploadImportService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testUpload()
    {
        $project = $this->entityManager->getRepository(Project::class)->findBy(
            ['archived' => false],
            ['id' => 'asc'],
            1
        )[0];
        $user = $this->entityManager->getRepository(User::class)->findBy([], ['id' => 'asc'], 1)[0];

        $import = new Import('KHM', 'test', null, [$project], $user);
        $this->entityManager->persist($import);
        $this->entityManager->flush();

        $uploadedFilePath = tempnam(sys_get_temp_dir(), 'import');

        $fs = new Filesystem();
        $fs->copy(__DIR__ . '/../../Resources/KHM-Import-2HH-3HHM-24HHM.ods', $uploadedFilePath, true);

        $file = new UploadedFile($uploadedFilePath, 'KHM-Import-2HH-3HHM-24HHM.ods', null, null, true);

        $this->uploadService->uploadFile($import, $file, $user);

        $queue = $this->entityManager->getRepository(ImportQueue::class)
            ->findBy(['import' => $import], ['id' => 'asc']);

        $this->assertCount(2, $queue);
        $this->assertSame(ImportQueueState::NEW, $queue[0]->getState());
        $this->assertSame('KHM-Import-2HH-3HHM-24HHM.ods', $queue[0]->getFile()->getFilename());
        $this->assertSame($import->getId(), $queue[0]->getImport()->getId());
        $this->assertIsArray($queue[0]->getContent());
    }
}
