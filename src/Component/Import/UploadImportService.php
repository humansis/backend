<?php

declare(strict_types=1);

namespace Component\Import;

use Component\Import\DBAL\InsertQueryCollection;
use Component\Import\Integrity;
use Component\Import\Messaging\Message\UploadFileFinished;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Import;
use Entity\ImportFile;
use Entity\User;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class UploadImportService
{
    private readonly \Component\Import\DBAL\InsertQueryCollection $sqlCollection;

    public function __construct(
        private readonly string $uploadDirectory,
        private readonly EntityManagerInterface $em,
        private readonly ImportFileValidator $importFileValidator,
        private readonly Integrity\DuplicityService $integrityDuplicityService,
        private readonly ImportParser $importParser,
        private readonly MessageBusInterface $messageBus
    ) {
        $this->sqlCollection = new InsertQueryCollection($em);
    }

    /**
     *
     * @throws ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function load(ImportFile $importFile): ImportFile
    {
        if ($importFile->isLoaded()) {
            throw new InvalidArgumentException('This import file is already loaded in database.');
        }
        if (null !== $importFile->getStructureViolations()) {
            throw new InvalidArgumentException('This import file has serious structure issues.');
        }

        $fileToImport = new File($this->uploadDirectory . '/' . $importFile->getSavedAsFilename());
        $list = $this->importParser->parse($fileToImport);

        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($list as $hhData) {
                // Original doctrine insert is too slow, do not use it.
                //
                // $queue = new ImportQueue($importFile->getImport(), $importFile, $hhData);
                // $this->em->persist($queue);
                // $this->em->flush();

                $this->sqlCollection->add($importFile, json_encode($hhData, JSON_THROW_ON_ERROR));
            }
            $this->sqlCollection->finish();

            $importFile->setSavedAsFilename(null);
            $importFile->setIsLoaded(true);

            $this->em->flush();
            $this->em->refresh($importFile->getImport());

            $this->integrityDuplicityService->buildIdentityTable($importFile->getImport());

            $fs = new Filesystem();
            $fs->remove($fileToImport->getRealPath());

            $this->em->getConnection()->commit();

            return $importFile;
        } catch (Exception $ex) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->getConnection()->rollBack();
            }

            throw $ex;
        }
    }

    public function uploadFile(Import $import, UploadedFile $uploadedFile, User $user): ImportFile
    {
        $savedAsFilename = time() . '-' . $uploadedFile->getClientOriginalName();

        $uploadedFile->move($this->uploadDirectory, $savedAsFilename);

        $importFile = new ImportFile($uploadedFile->getClientOriginalName(), $import, $user);
        $importFile->setSavedAsFilename($savedAsFilename);

        $this->importFileValidator->validate($importFile);

        $this->em->persist($importFile);
        $this->em->flush();

        $this->messageBus->dispatch(new UploadFileFinished($importFile->getId()));

        return $importFile;
    }
}
