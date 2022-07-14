<?php
namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\NationalId;

class NationalIdMapper
{
    public function toFullArray(?NationalId $nationalId): ?array
    {
        if (!$nationalId) return null;
        return [
            "id" => $nationalId->getId(),
            "type" => $nationalId->getIdType(),
            "number" => $nationalId->getIdNumber(),
        ];
    }

    public function toFullArrays(iterable $nationalIds)
    {
        foreach ($nationalIds as $nationalId) {
            yield $this->toFullArray($nationalId);
        }
    }
}
