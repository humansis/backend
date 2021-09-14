<?php

namespace Tests\NewApiBundle\Api;

class DummyEntityObject2
{
    /**
     * @var boolean
     */
    private $archived = false;

    /**
     * @var string
     */
    private $simpleName = 'placeholder';

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
    public function getArchived(): bool
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
    public function simpleName(): string
    {
        return $this->simpleName;
    }

    /**
     * @param string $simpleName
     */
    public function setSimpleName(string $simpleName): void
    {
        $this->simpleName = $simpleName;
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
