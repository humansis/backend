<?php
namespace VoucherBundle\Mapper;

use NewApiBundle\Entity\Community;
use NewApiBundle\MapperDeprecated\LocationMapper;
use NewApiBundle\Entity\Smartcard;

class SmartcardMapper
{
    /**
     * @param Smartcard|null $smartcard
     *
     * @return array
     */
    public function toFullArray(?Smartcard $smartcard): ?array
    {
        if (!$smartcard) return null;
        return [
            "id" => $smartcard->getId(),
            "serialNumber" => $smartcard->getSerialNumber(),
            "currency" => $smartcard->getCurrency(),
            "state" => $smartcard->getState(),
        ];
    }

    public function toFullArrays(array $smartcards)
    {
        foreach ($smartcards as $smartcard) {
            yield $this->toFullArray($smartcard);
        }
    }
}
