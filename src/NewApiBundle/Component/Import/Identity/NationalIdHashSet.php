<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Identity;

use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\ImportQueue;

class NationalIdHashSet
{
    private $hashSet = [];
    /** @var ImportQueue[] */
    private $items = [];
    /** @var string[] */
    private $types = [];
    /** @var string[] */
    private $numbers = [];

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
     * @param NationalId $nationalId
     * @param callable   $callbackForAllItems(ImportQueue $item, int $index, NationalId $nationalId)
     */
    public function forItems(NationalId $nationalId, callable $callbackForAllItems): void
    {
        if (!$this->hasItems($nationalId)) return;
        foreach ($this->hashSet[$nationalId->getIdType()][$nationalId->getIdNumber()] as $itemCouple) {
            list($itemId, $index) = $itemCouple;
            $item = $this->items[$itemId];

            $callbackForAllItems($item, $index, $nationalId);
        }
    }

}
