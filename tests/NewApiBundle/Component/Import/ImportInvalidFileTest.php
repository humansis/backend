<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\ImportInvalidFileService;
use NewApiBundle\Component\Import\UploadImportService;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserBundle\Entity\User;

class ImportInvalidFileTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private static $entityManager;

    /** @var ImportInvalidFileService */
    private static $importInvalidFileService;

    /** @var string */
    private static $invalidFilesDirectory;

    /** @var UploadImportService  */
    private static $importUploadService;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        self::$importInvalidFileService = $kernel->getContainer()->get(ImportInvalidFileService::class);
        self::$invalidFilesDirectory = $kernel->getContainer()->getParameter('importInvalidFilesDirectory');
        self::$importUploadService = $kernel->getContainer()->get(UploadImportService::class);
    }

    public function testCreateInvalidFileAndImportAgain()
    {
        /** @var Import $import */
        $import = self::$entityManager->getRepository(Import::class)->findOneBy([]);

        if (is_null($import)) {
            $this->markTestSkipped('There needs to be at least one import in system');
        }

        /** @var User $user */
        $user = self::$entityManager->getRepository(User::class)->findOneBy([]);

        $invalidFilePath = self::$importInvalidFileService->generateFile($import);

        $uploadFile = new UploadedFile(self::$invalidFilesDirectory.'/'.$invalidFilePath->getFilename(), $invalidFilePath->getFilename());

        self::$importUploadService->upload($import, $uploadFile, $user);

        $importedFile = self::$entityManager->getRepository(ImportFile::class)
            ->findOneBy([
                'filename' => $invalidFilePath->getFilename(),
            ]);

        $this->assertNotNull($importedFile);
    }
}
