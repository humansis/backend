<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use NewApiBundle\Component\Import\DBAL\InsertQueryCollection;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserBundle\Entity\User;

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

    public function __construct(EntityManagerInterface $em, string $uploadDirectory)
    {
        $this->parser = new ImportParser();
        $this->em = $em;
        $this->sqlCollection = new InsertQueryCollection($em);
        $this->uploadDirectory = $uploadDirectory;
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

        $this->em->persist($importFile);
        $this->em->flush();

        return $importFile;
    }
}
