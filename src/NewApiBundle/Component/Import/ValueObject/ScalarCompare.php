<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\ValueObject;

class ScalarCompare
{
    /** @var mixed|null */
    private $import;
    /** @var mixed|null */
    private $database;

    /**
     * @param mixed|null $import
     * @param mixed|null $database
     */
    public function __construct($import, $database)
    {
        $this->import = $import;
        $this->database = $database;
    }

    /**
     * @return mixed|null
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @return mixed|null
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
