<?php

namespace Tests\NewApiBundle\Api;

class DummyEntityObject
{
    /**
     * @var boolean
     */
    private $archived = false;

    /**
     * @var string
     */
    private $simple_name = 'placeholder';

    /**
     * @var string[]
     */
    private $items = ['single placeholder value'];

    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     */
    public function setArchived(bool $archived): void
    {
        $this->archived = $archived;
    }

    /**
     * @return string
     */
    public function getSimpleName(): string
    {
        return $this->simple_name;
    }

    /**
     * @param string $simple_name
     */
    public function setSimpleName(string $simple_name): void
    {
        $this->simple_name = $simple_name;
    }

    /**
     * @return string[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param string[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

}
