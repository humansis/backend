<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

class ListCompare
{
    /** @var string[] */
    private $import;
    /** @var string[] */
    private $database;
    /** @var string[] */
    private $same;

    /**
     * @param string[] $import
     * @param string[] $database
     * @param string[] $same
     */
    public function __construct(array $import, array $database, array $same)
    {
        $this->import = $import;
        $this->database = $database;
        $this->same = $same;
    }

    /**
     * @return string[]
     */
    public function getImport(): array
    {
        return $this->import;
    }

    /**
     * @return string[]
     */
    public function getDatabase(): array
    {
        return $this->database;
    }

    /**
     * @return string[]
     */
    public function getSame(): array
    {
        return $this->same;
    }



}
