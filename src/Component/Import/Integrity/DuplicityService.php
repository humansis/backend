<?php

declare(strict_types=1);

namespace Component\Import\Integrity;

use Entity\Import;
use Enum\ImportQueueState;
use InputType\Beneficiary\NationalIdCardInputType;
use RuntimeException;
use Utils\FileSystem\PathConstructor;

class DuplicityService
{
    public function __construct(private readonly ImportLineFactory $lineFactory, private readonly string $cacheFilePath)
    {
    }

    public function buildIdentityTable(Import $import): void
    {
        $identities = [];
        foreach ($import->getImportQueue() as $item) {
            if (
                !in_array($item->getState(), [
                ImportQueueState::NEW,
                ImportQueueState::VALID,
                ])
            ) {
                continue;
            } // ignore non-importing states
            foreach ($this->lineFactory->createAll($item) as $memberIndex => $line) {
                foreach ($line->getFilledIds() as $idItem) {
                    $cardSerialization = self::serializeIDCard((string) $idItem['type'], (string) $idItem['number']);
                    $identities[$cardSerialization][$item->getId()][] = $memberIndex;
                }
            }
        }
        $fileName = $this->getFileName($import);
        if (false === file_put_contents($fileName, json_encode($identities, JSON_THROW_ON_ERROR))) {
            throw new RuntimeException("File $fileName couldn't be written");
        }
    }

    public function getIdentityCount(Import $import, NationalIdCardInputType $idCard): int
    {
        $fileName = $this->getFileName($import);
        $identityData = file_get_contents($fileName);
        if ($identityData === false) {
            throw new RuntimeException("File $fileName missing");
        }
        $identities = json_decode($identityData, true, 512, JSON_THROW_ON_ERROR);
        $identity = self::serializeIDCard($idCard->getOriginalType(), $idCard->getNumber());

        if (isset($identities[$identity])) {
            // TODO: count subduplicity

            // COUNT RECURSIVE could not get exact results but should be faster than iteration
            return (is_countable($identities[$identity]) ? count($identities[$identity], COUNT_RECURSIVE) : 0) - 1;
        }

        return 0;
    }

    private static function serializeIDCard(string $type, string $number): string
    {
        return $type . "=" . $number;
    }

    private function getFileName(Import $import): string
    {
        return PathConstructor::construct($this->cacheFilePath, ['importId' => $import->getId()]);
    }
}
