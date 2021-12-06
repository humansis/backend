<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\DBAL;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Entity\File;
use NewApiBundle\Component\Import\Enum\QueueState;

class InsertQueryCollection
{
    private $params = [];

    private $counter = 0;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function add(File $importFile, string $content)
    {
        array_push($this->params, $importFile->getImport()->getId(), $importFile->getId(), $content, QueueState::NEW);

        if (500 === ++$this->counter) {
            $this->save();
        }
    }

    public function finish()
    {
        $this->save();
    }

    private function save()
    {
        $sql = 'INSERT INTO `import_queue`(`import_id`, `file_id`, `content`, `state`) VALUES '.substr(str_repeat('(?,?,?,?),', $this->counter), 0, -1);

        $this->em->getConnection()->executeQuery($sql, $this->params);

        $this->params = [];
        $this->counter = 0;
    }
}
