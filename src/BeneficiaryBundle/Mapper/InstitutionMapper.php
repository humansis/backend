<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\Institution;

class InstitutionMapper
{
    /** @var AddressMapper */
    private $addressMapper;
    /** @var NationalIdMapper */
    private $nationalIdMapper;
    /** @var PersonMapper */
    private $personMapper;

    /**
     * CommunityMapper constructor.
     *
     * @param AddressMapper    $addressMapper
     * @param NationalIdMapper $nationalIdMapper
     * @param PersonMapper     $personMapper
     */
    public function __construct(AddressMapper $addressMapper, NationalIdMapper $nationalIdMapper, PersonMapper $personMapper)
    {
        $this->addressMapper = $addressMapper;
        $this->nationalIdMapper = $nationalIdMapper;
        $this->personMapper = $personMapper;
    }

    /**
     * @param Institution|null $institution
     *
     * @return array
     */
    public function toFullArray(?Institution $institution): ?array
    {
        if (!$institution) {
            return null;
        }
        return [
            "id" => $institution->getId(),
            "name" => $institution->getName(),
            "type" => $institution->getType(),
            "contact_name" => $institution->getContactName(),
            "contact_family_name" => $institution->getContactFamilyName(),
            "phone_number" => $institution->getPhoneNumber(),
            "phone_prefix" => $institution->getPhonePrefix(),
            "national_id" => $this->nationalIdMapper->toFullArray($institution->getNationalId()),
            "address" => $this->addressMapper->toFlatArray($institution->getAddress()),
            "latitude" => $institution->getLatitude(),
            "longitude" => $institution->getLongitude(),
            "contact" => $this->personMapper->toFullArray($institution->getContact()),
        ];
    }

    /**
     * @param Institution[] $institutions
     * @return \Generator
     */
    public function toFullArrays(array $institutions)
    {
        foreach ($institutions as $institution) {
            yield $this->toFullArray($institution);
        }
    }
}
