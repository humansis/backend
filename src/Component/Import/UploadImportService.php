<?php declare(strict_types=1);

namespace Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Component\Import\DBAL\InsertQueryCollection;
use Component\Import\Integrity;
use Component\Import\Messaging\Message\UploadFile;
use Entity\Import;
use Entity\ImportFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Entity\User;
use Symfony\Component\Messenger\MessageBusInterface;

class UploadImportService
{
    /** @var ImportParser */
    private $parser;

    /** @var EntityManagerInterface */
    private $em;

    /** @var InsertQueryCollection */
    private $sqlCollection;

    /** @var string */
    private $uploadDirectory;

    /** @var ImportFileValidator */
    private $importFileValidator;

    /** @var Integrity\DuplicityService */
    private $integrityDuplicityService;

    /** @var MessageBusInterface */
    private $messageBus;



    public function __construct(
        string                     $uploadDirectory,
        EntityManagerInterface     $em,
        ImportFileValidator        $importFileValidator,
        Integrity\DuplicityService $integrityDuplicityService,
        MessageBusInterface $messageBus
    )
    {
        $this->parser = new ImportParser();
        $this->em = $em;
        $this->sqlCollection = new InsertQueryCollection($em);
        $this->uploadDirectory = $uploadDirectory;
        $this->importFileValidator = $importFileValidator;
        $this->integrityDuplicityService = $integrityDuplicityService;
        $this->messageBus = $messageBus;
    }



    /**
     * @param ImportFile $importFile
     *
     * @return ImportFile
     * @throws \Doctrine\DBAL\ConnectionException
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

        $fileToImport = new File($this->uploadDirectory.'/'.$importFile->getSavedAsFilename());
        $list = $this->parser->parse($fileToImport);

        $this->em->getConnection()->beginTransaction();
        try {
            foreach ($list as $hhData) {
                // Original doctrine insert is too slow, do not use it.
                //
                // $queue = new ImportQueue($importFile->getImport(), $importFile, $hhData);
                // $this->em->persist($queue);
                // $this->em->flush();

                $this->sqlCollection->add($importFile, json_encode($hhData));
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
        } catch (\Exception $ex) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->getConnection()->rollBack();
            }

            throw $ex;
        }
    }

    /**
     * @param Import       $import
     * @param UploadedFile $uploadedFile
     * @param User         $user
     *
     * @return ImportFile
     */
    public function uploadFile(Import $import, UploadedFile $uploadedFile, User $user): ImportFile
    {
        $savedAsFilename = time().'-'.$uploadedFile->getClientOriginalName();

        $uploadedFile->move($this->uploadDirectory, $savedAsFilename);

        $importFile = new ImportFile($uploadedFile->getClientOriginalName(), $import, $user);
        $importFile->setSavedAsFilename($savedAsFilename);

        $this->importFileValidator->validate($importFile);

        $this->em->persist($importFile);
        $this->em->flush();

        if (!$importFile->getStructureViolations()) {
            $this->messageBus->dispatch(new UploadFile($importFile->getId()));
        }

        return $importFile;
    }
}