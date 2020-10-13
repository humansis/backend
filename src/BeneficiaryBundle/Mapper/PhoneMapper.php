<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;

class PhoneMapper
{
    public function toFullArray(?Phone $phone): ?array
    {
        if (!$phone) {
            return null;
        }
        return [
            "id" => $phone->getId(),
            "type" => $phone->getType(),
            "number" => $phone->getNumber(),
            "proxy" => $phone->getProxy(),
            "prefix" => $phone->getPrefix(),
        ];
    }

    public function toFullArrays(iterable $phones)
    {
        foreach ($phones as $phone) {
            yield $this->toFullArray($phone);
        }
    }
}
