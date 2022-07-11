<?php declare(strict_types=1);

namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\Community;
use CommonBundle\Mapper\LocationMapper;
use ProjectBundle\Mapper\ProjectMapper;

class CommunityMapper
{
    /** @var AddressMapper */
    private $addressMapper;
    /** @var NationalIdMapper */
    private $nationalIdMapper;
    /** @var LocationMapper */
    private $locationMapper;
    /** @var PersonMapper */
    private $personMapper;
    /** @var ProjectMapper */
    private $projectMapper;

    /**
     * CommunityMapper constructor.
     *
     * @param AddressMapper    $addressMapper
     * @param NationalIdMapper $nationalIdMapper
     * @param LocationMapper   $locationMapper
     * @param PersonMapper     $personMapper
     * @param ProjectMapper    $projectMapper
     */
    public function __construct(
        AddressMapper $addressMapper,
        NationalIdMapper $nationalIdMapper,
        LocationMapper $locationMapper,
        PersonMapper $personMapper,
        ProjectMapper $projectMapper
    )
    {
        $this->addressMapper = $addressMapper;
        $this->nationalIdMapper = $nationalIdMapper;
        $this->locationMapper = $locationMapper;
        $this->personMapper = $personMapper;
        $this->projectMapper = $projectMapper;
    }

    /**
     * @param Community|null $community
     *
     * @return array
     */
    public function toFullArray(?Community $community): ?array
    {
        if (!$community) {
            return null;
        }
        return [
            "id" => $community->getId(),
            "name" => $this->getName($community),
            "contact_name" => $community->getContactName(),
            "contact_family_name" => $community->getContactFamilyName(),
            "phone_number" => $community->getPhoneNumber(),
            "phone_prefix" => $community->getPhonePrefix(),
            "national_id" => $this->nationalIdMapper->toFullArray($community->getNationalId()),
            "address" => $this->addressMapper->toFlatArray($community->getAddress()),
            "latitude" => $community->getLatitude(),
            "longitude" => $community->getLongitude(),
            "contact" => $this->personMapper->toFullArray($community->getContact()),
            "projects" => $this->projectMapper->toIdArray($community->getProjects()),
        ];
    }

    private function getName(Community $community): string
    {
        if (!$community->getAddress() || !$community->getAddress()->getLocation()) {
            return "global community";
        }
        return $this->locationMapper->toName($community->getAddress()->getLocation());
    }
}
