<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\DBAL\InsertQueryCollection;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportFile;
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

    public function __construct(EntityManagerInterface $em)
    {
        $this->parser = new ImportParser();
        $this->em = $em;
        $this->sqlCollection = new InsertQueryCollection($em);
    }

    /**
     * @param Import       $import
     * @param UploadedFile $uploadedFile
     * @param User         $user
     *
     * @return ImportFile
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function upload(Import $import, UploadedFile $uploadedFile, User $user): ImportFile
    {
        $list = $this->parser->parse($uploadedFile);

        $this->em->getConnection()->beginTransaction();
        try {
            $importFile = new ImportFile($uploadedFile->getFilename(), $import, $user);
            $this->em->persist($importFile);
            $this->em->flush();

            foreach ($list as $hhData) {
                // Original doctrine insert is too slow, do not use it.
                //
                // $queue = new ImportQueue($importFile->getImport(), $importFile, $hhData);
                // $this->em->persist($queue);
                // $this->em->flush();

                $this->sqlCollection->add($importFile, json_encode($hhData));
            }
            $this->sqlCollection->finish();

            $this->em->getConnection()->commit();

            return $importFile;
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollBack();
            throw $ex;
        }
    }

    public function uploadFile(Import $import, UploadedFile $uploadedFile, User $user): ImportFile
    {
        $uploadedFile->move();
    }
}
