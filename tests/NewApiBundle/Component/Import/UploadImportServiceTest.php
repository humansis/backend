<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportQueueState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadImportServiceTest extends KernelTestCase
{
    /** @var UploadImportService */
    private $uploadService;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp()
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->uploadService = new UploadImportService($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testUpload()
    {
        $project = $this->entityManager->getRepository(\ProjectBundle\Entity\Project::class)->findBy(['archived' => false], null, 1)[0];
        $user = $this->entityManager->getRepository(\UserBundle\Entity\User::class)->findBy([], null, 1)[0];

        $import = new Import('test', null, $project, $user);
        $this->entityManager->persist($import);
        $this->entityManager->flush();

        $file = new UploadedFile(__DIR__.'/../../Resources/Import.ods', 'Import.ods');

        $this->uploadService->upload($import, $file, $user);

        $queue = $this->entityManager->getRepository(\NewApiBundle\Entity\ImportQueue::class)->findBy(['import' => $import]);

        $this->assertCount(2, $queue);
        $this->assertSame(ImportQueueState::NEW, $queue[0]->getState());
        $this->assertSame('Import.ods', $queue[0]->getFile()->getFilename());
        $this->assertSame($import->getId(), $queue[0]->getImport()->getId());
        $this->assertIsArray($queue[0]->getContent());
    }
}
