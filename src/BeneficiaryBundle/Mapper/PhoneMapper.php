<?php
namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Person;
use NewApiBundle\Entity\Phone;

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
