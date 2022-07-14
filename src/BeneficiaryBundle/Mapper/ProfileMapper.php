<?php
namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Person;
use NewApiBundle\Entity\Phone;
use NewApiBundle\Entity\Profile;

class ProfileMapper
{
    public function toFullArray(?Profile $profile): ?array
    {
        if (!$profile) {
            return null;
        }
        return [
            "id" => $profile->getId(),
            "photo" => $profile->getPhoto(),
        ];
    }

    public function toFullArrays(iterable $photos)
    {
        foreach ($photos as $photo) {
            yield $this->toFullArray($photo);
        }
    }
}
