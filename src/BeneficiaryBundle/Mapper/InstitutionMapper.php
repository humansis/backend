<?php declare(strict_types=1);

namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\Institution;
use ProjectBundle\Mapper\ProjectMapper;

class InstitutionMapper
{
    /** @var AddressMapper */
    private $addressMapper;
    /** @var NationalIdMapper */
    private $nationalIdMapper;
    /** @var PersonMapper */
    private $personMapper;
    /** @var ProjectMapper */
    private $projectMapper;

    /**
     * CommunityMapper constructor.
     *
     * @param AddressMapper $addressMapper
     * @param NationalIdMapper $nationalIdMapper
     * @param PersonMapper $personMapper
     * @param ProjectMapper $projectMapper
     */
    public function __construct(
        AddressMapper $addressMapper,
        NationalIdMapper $nationalIdMapper,
        PersonMapper $personMapper,
        ProjectMapper $projectMapper
    ) {
        $this->addressMapper = $addressMapper;
        $this->nationalIdMapper = $nationalIdMapper;
        $this->personMapper = $personMapper;
        $this->projectMapper = $projectMapper;
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
            "projects" => $this->projectMapper->toIdArray($institution->getProjects()),
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
