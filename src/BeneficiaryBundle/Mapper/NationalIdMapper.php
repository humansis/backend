<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\NationalId;

class NationalIdMapper
{
    public function toFullArray(?NationalId $nationalId): ?array
    {
        if (!$nationalId) return null;
        return [
            "id" => $nationalId->getId(),
            "type" => $nationalId->getIdType(),
            "number" => $nationalId->getIdNumber(),
            "priority" => $nationalId->getPriority(),
        ];
    }

    public function toFullArrays(iterable $nationalIds)
    {
        foreach ($nationalIds as $nationalId) {
            yield $this->toFullArray($nationalId);
        }
    }
}
