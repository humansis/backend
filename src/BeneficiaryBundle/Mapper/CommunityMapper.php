<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\Community;

class CommunityMapper
{
    /** @var AddressMapper */
    private $addressMapper;
    /** @var NationalIdMapper */
    private $nationalIdMapper;

    /**
     * CommunityMapper constructor.
     * @param AddressMapper $addressMapper
     * @param NationalIdMapper $nationalIdMapper
     */
    public function __construct(AddressMapper $addressMapper, NationalIdMapper $nationalIdMapper)
    {
        $this->addressMapper = $addressMapper;
        $this->nationalIdMapper = $nationalIdMapper;
    }

    /**
     * @param Community $community
     * @return array
     */
    public function toFullArray(?Community $community): ?array
    {
        if (!$community) return null;
        return [
            "contact_name" => $community->getContactName(),
            "contact_family_name" => $community->getContactFamilyName(),
            "phone_number" => $community->getPhoneNumber(),
            "phone_prefix" => $community->getPhonePrefix(),
            "national_id" => $this->nationalIdMapper->toFullArray($community->getNationalId()),
            "address" => $this->addressMapper->toFlatArray($community->getAddress()),
            "latitude" => $community->getLatitude(),
            "longitude" => $community->getLongitude(),
        ];
    }

    public function toFullArrays(array $communities)
    {
        foreach ($communities as $community) {
            yield $this->toFullArray($community);
        }
    }
}
