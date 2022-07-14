<?php
namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\Person;
use NewApiBundle\Enum\PersonGender;

class PersonMapper
{
    /** @var PhoneMapper */
    private $phoneMapper;
    /** @var NationalIdMapper */
    private $nationalIdMapper;
    /** @var ProfileMapper */
    private $profileMapper;

    /**
     * PersonMapper constructor.
     *
     * @param PhoneMapper      $phoneMapper
     * @param NationalIdMapper $nationalIdMapper
     * @param ProfileMapper    $profileMapper
     */
    public function __construct(PhoneMapper $phoneMapper, NationalIdMapper $nationalIdMapper, ProfileMapper $profileMapper)
    {
        $this->phoneMapper = $phoneMapper;
        $this->nationalIdMapper = $nationalIdMapper;
        $this->profileMapper = $profileMapper;
    }

    public function toFullArray(?Person $person): ?array
    {
        if (!$person) {
            return null;
        }
        return [
            "id" => $person->getId(),
            "en_given_name" => $person->getEnGivenName(),
            "en_family_name" => $person->getEnFamilyName(),
            "local_given_name" => $person->getLocalGivenName(),
            "local_family_name" => $person->getLocalFamilyName(),
            "phones" => $this->phoneMapper->toFullArrays($person->getPhones()),
            "national_ids" => $this->nationalIdMapper->toFullArrays($person->getNationalIds()),
            "profile" => $this->profileMapper->toFullArray($person->getProfile()),
            "gender" => $person->getGender() ? PersonGender::valueToAPI($person->getGender()) : null,
            "referral" => $person->getReferral(),
            "date_of_birth" => $person->getDateOfBirth() ? $person->getDateOfBirth()->format('d-m-Y') : null,
            "age" => $person->getAge(),
            "local_parents_name" => $person->getLocalParentsName(),
            "en_parents_name" => $person->getEnParentsName(),
        ];
    }

    public function toFullArrays(iterable $persons)
    {
        foreach ($persons as $person) {
            yield $this->toFullArray($person);
        }
    }
}
