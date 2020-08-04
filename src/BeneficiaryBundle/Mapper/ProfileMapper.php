<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;

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
