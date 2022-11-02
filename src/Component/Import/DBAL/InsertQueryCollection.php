<?php

declare(strict_types=1);

namespace Component\Import\DBAL;

use Doctrine\ORM\EntityManagerInterface;
use Entity\ImportFile;
use Enum\ImportQueueState;

class InsertQueryCollection
{
    private array $params = [];

    private int $counter = 0;

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function add(ImportFile $importFile, string $content)
    {
        array_push(
            $this->params,
            $importFile->getImport()->getId(),
            $importFile->getId(),
            $content,
            ImportQueueState::NEW
        );

        if (500 === ++$this->counter) {
            $this->save();
        }
    }

    public function finish()
    {
        if ($this->counter > 0) {
            $this->save();
        }
    }

    private function save()
    {
        $sql = 'INSERT INTO `import_queue`(`import_id`, `file_id`, `content`, `state`) VALUES '
            . substr(str_repeat('(?,?,?,?),', $this->counter), 0, -1);

        $this->em->getConnection()->executeQuery($sql, $this->params);

        $this->params = [];
        $this->counter = 0;
    }
}
