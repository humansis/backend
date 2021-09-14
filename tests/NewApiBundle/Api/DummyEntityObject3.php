<?php

namespace Tests\NewApiBundle\Api;

class DummyEntityObject3
{
    /**
     * @var boolean
     */
    private $removed = false;

    /**
     * @var string
     */
    private $veryLong_name = 'placeholder';

    /**
     * @var string[]
     */
    private $things = ['single placeholder value'];

    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public function getRemoved(): bool
    {
        return $this->removed;
    }

    /**
     * @param bool $removed
     */
    public function setRemoved(bool $removed): void
    {
        $this->removed = $removed;
    }

    /**
     * @return string
     */
    public function simpleName(): string
    {
        return $this->veryLong_name;
    }

    /**
     * @param string $veryLong_name
     */
    public function setVeryLongname(string $veryLong_name): void
    {
        $this->veryLong_name = $veryLong_name;
    }

    /**
     * @return string[]
     */
    public function getThings(): array
    {
        return $this->things;
    }

    /**
     * @param string[] $things
     */
    public function setThings(array $things): void
    {
        $this->things = $things;
    }

}
