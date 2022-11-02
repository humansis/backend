<?php

declare(strict_types=1);

namespace Component\Import\Identity;

use Entity\NationalId;
use Entity\ImportQueue;

class NationalIdHashSet
{
    private array $hashSet = [];

    /** @var ImportQueue[] */
    private array $items = [];

    /** @var string[] */
    private array $types = [];

    /** @var string[] */
    private array $numbers = [];

    public function add(ImportQueue $item, int $index, string $idType, string $idNumber): void
    {
        $this->items[$item->getId()] = $item;
        $this->numbers[] = $idNumber;
        $this->types[] = $idType;
        $this->hashSet[$idType][$idNumber][] = [$item->getId(), $index];
    }

    public function getNumbers(): array
    {
        return array_unique($this->numbers);
    }

    public function getTypes(): array
    {
        return array_unique($this->types);
    }

    private function hasItems(NationalId $nationalId): bool
    {
        return !empty($this->hashSet[$nationalId->getIdType()])
            && !empty($this->hashSet[$nationalId->getIdType()][$nationalId->getIdNumber()]);
    }

    /**
     * @param callable $callbackForAllItems (ImportQueue $item, int $index, NationalId $nationalId)
     */
    public function forItems(NationalId $nationalId, callable $callbackForAllItems): void
    {
        if (!$this->hasItems($nationalId)) {
            return;
        }
        foreach ($this->hashSet[$nationalId->getIdType()][$nationalId->getIdNumber()] as $itemCouple) {
            [$itemId, $index] = $itemCouple;
            $item = $this->items[$itemId];

            $callbackForAllItems($item, $index, $nationalId);
        }
    }
}
