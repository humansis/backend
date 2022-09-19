<?php declare(strict_types=1);

namespace Component\Import\Integrity;

use Entity\Import;
use Enum\ImportQueueState;
use InputType\Beneficiary\NationalIdCardInputType;
use Utils\FileSystem\PathConstructor;

class DuplicityService
{
    /** @var ImportLineFactory */
    private $lineFactory;

    /** @var string */
    private $cacheFilePath;

    /**
     * @param ImportLineFactory $lineFactory
     * @param string            $cacheFilePath
     */
    public function __construct(ImportLineFactory $lineFactory, string $cacheFilePath)
    {
        $this->lineFactory = $lineFactory;
        $this->cacheFilePath = $cacheFilePath;
    }

    public function buildIdentityTable(Import $import): void
    {
        $identities = [];
        foreach ($import->getImportQueue() as $item) {
            if (!in_array($item->getState(), [
                ImportQueueState::NEW,
                ImportQueueState::VALID,
            ])) continue; // ignore non-importing states
            foreach ($this->lineFactory->createAll($item) as $memberIndex => $line) {
                foreach ($line->getFilledIds() as $idItem) {
                    $cardSerialization = self::serializeIDCard((string)$idItem['type'], (string)$idItem['number']);
                    $identities[$cardSerialization][$item->getId()][] = $memberIndex;
                }
            }
        }
        $fileName = $this->getFileName($import);
        if (false === file_put_contents($fileName, json_encode($identities))) {
            throw new \RuntimeException("File $fileName couldn't be written");
        }
    }

    public function getIdentityCount(Import $import, NationalIdCardInputType $idCard): int
    {
        $fileName = $this->getFileName($import);
        $identityData = file_get_contents($fileName);
        if ($identityData === false) {
            throw new \RuntimeException("File $fileName missing");
        }
        $identities = json_decode($identityData, true);
        $identity = self::serializeIDCard($idCard->getOriginalType(), $idCard->getNumber());

        if (isset($identities[$identity])) {
            // TODO: count subduplicity

            // COUNT RECURSIVE could not get exact results but should be faster than iteration
            return count($identities[$identity], COUNT_RECURSIVE) - 1;
        }
        return 0;
    }

    private static function serializeIDCard(string $type, string $number): string
    {
        return $type."=".$number;
    }

    private function getFileName(Import $import): string
    {
        return PathConstructor::construct($this->cacheFilePath, ['importId' => $import->getId()]);
    }
}
