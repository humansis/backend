<?php


namespace ProjectBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use ProjectBundle\DBAL\SectorEnum;
use ProjectBundle\DBAL\SubSectorEnum;
use ProjectBundle\DTO\Sector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SectorService
 * @package ProjectBundle\Utils
 */
class SectorService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var ValidatorInterface $validator */
    private $validator;

    /**
     * SectorService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer, ValidatorInterface $validator)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function findBySubSector($subSectorName): ?Sector
    {
        $sector = $this->findSector($subSectorName);
        if (!$sector) {
            return null;
        }
        switch ($sector->getSubSectorName()) {
            case SubSectorEnum::FOOD_PARCELS_BASKETS:
            case SubSectorEnum::RTER:
            case SubSectorEnum::FOOD_VOUCHERS:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::CASH_FOR_WORK:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::SKILLS_TRAINING:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::TECHNICAL_SUPPORT:
            case SubSectorEnum::DISTRIBUTION_OF_INPUTS:
            case SubSectorEnum::BUSINESS_GRANTS:
            case SubSectorEnum::AGRICULTURAL_VOUCHERS:
                return $sector->setDistributionAllowed()
                    ;
            case SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::REHABILITATION:
            case SubSectorEnum::CONSTRUCTION:
                return $sector->setActivityAllowed()
                    ->setInstitutionAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::SETTLEMENT_UPGRADES:
                return $sector->setActivityAllowed()
                    ->setCommunityAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::WINTERIZATION_KITS:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::WINTERIZATION_UPGRADES:
                return $sector->setActivityAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::SHELTER_KITS:
            case SubSectorEnum::NFI_KITS:
            case SubSectorEnum::CASH_FOR_SHELTER:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::WATER_POINT_REHABILITATION:
            case SubSectorEnum::WATER_POINT_CONSTRUCTION:
            case SubSectorEnum::WATER_TRUCKING:
            case SubSectorEnum::WATER_TREATMENT:
            case SubSectorEnum::VECTOR_CONTROL:
            case SubSectorEnum::SOLID_WASTE_MANAGEMENT:
            case SubSectorEnum::SANITATION:
                return $sector->setActivityAllowed()
                    ->setHouseholdAllowed()
                    ->setCommunityAllowed()
                    ->setInstitutionAllowed()
                    ;
            case SubSectorEnum::HYGIENE_PROMOTION:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ->setCommunityAllowed()
                    ->setInstitutionAllowed()
                    ;
            case SubSectorEnum::HYGIENE_KITS:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::OPERATIONAL_SUPPLIES:
                return $sector->setDistributionAllowed()
                    ->setHouseholdAllowed()
                    ->setInstitutionAllowed()
                    ;
            case SubSectorEnum::PROTECTION_PSYCHOSOCIAL_SUPPORT:
            case SubSectorEnum::INDIVIDUAL_PROTECTION_ASSISTANCE:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::COMMUNITY_BASED_INTERVENTIONS:
                return $sector->setActivityAllowed()
                    ->setCommunityAllowed()
                    ;
            case SubSectorEnum::PROTECTION_ADVOCACY:
                return $sector->setDistributionAllowed()
                    ->setInstitutionAllowed()
                    ;
            case SubSectorEnum::CHILD_PROTECTION:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ->setCommunityAllowed()
                    ->setInstitutionAllowed()
                    ;
            case SubSectorEnum::GENDER_BASED_VIOLENCE_ACTIVITIES:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ->setCommunityAllowed()
                    ->setInstitutionAllowed()
                    ->setHouseholdAllowed()
                    ;
            case SubSectorEnum::TEACHER_INCENTIVE_PAYMENTS:
                return $sector->setDistributionAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::TEACHER_TRAINING:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            case SubSectorEnum::LEARNING_MATERIALS:
                return $sector->setDistributionAllowed()
                    ->setBeneficiaryAllowed()
                    ->setInstitutionAllowed()
                    ;
            case SubSectorEnum::EDUCATION_PSYCHOSOCIAL_SUPPORT:
            case SubSectorEnum::EDUCATION_SERVICES:
                return $sector->setActivityAllowed()
                    ->setBeneficiaryAllowed()
                    ;
            default:
                return null;
        }
    }

    /**
     * @param $subSectorName
     *
     * @return Sector|null
     */
    private function findSector($subSectorName): ?Sector
    {
        switch ($subSectorName) {
            case SubSectorEnum::FOOD_PARCELS_BASKETS:
            case SubSectorEnum::RTER:
            case SubSectorEnum::FOOD_VOUCHERS:
            case SubSectorEnum::CASH_FOR_WORK:
                return new Sector(SectorEnum::FOOD_SECURITY, $subSectorName);

            case SubSectorEnum::SKILLS_TRAINING:
            case SubSectorEnum::TECHNICAL_SUPPORT:
            case SubSectorEnum::DISTRIBUTION_OF_INPUTS:
            case SubSectorEnum::BUSINESS_GRANTS:
            case SubSectorEnum::AGRICULTURAL_VOUCHERS:
                return new Sector(SectorEnum::LIVELIHOODS, $subSectorName);

            case SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE:
                return new Sector(SectorEnum::MULTIPURPOSE_CASH, $subSectorName);

            case SubSectorEnum::REHABILITATION:
            case SubSectorEnum::CONSTRUCTION:
            case SubSectorEnum::SETTLEMENT_UPGRADES:
            case SubSectorEnum::WINTERIZATION_KITS:
            case SubSectorEnum::WINTERIZATION_UPGRADES:
            case SubSectorEnum::SHELTER_KITS:
            case SubSectorEnum::NFI_KITS:
            case SubSectorEnum::CASH_FOR_SHELTER:
                return new Sector(SectorEnum::SHELTER, $subSectorName);

            case SubSectorEnum::WATER_POINT_REHABILITATION:
            case SubSectorEnum::WATER_POINT_CONSTRUCTION:
            case SubSectorEnum::WATER_TRUCKING:
            case SubSectorEnum::WATER_TREATMENT:
            case SubSectorEnum::VECTOR_CONTROL:
            case SubSectorEnum::SOLID_WASTE_MANAGEMENT:
            case SubSectorEnum::SANITATION:
            case SubSectorEnum::HYGIENE_PROMOTION:
            case SubSectorEnum::HYGIENE_KITS:
            case SubSectorEnum::OPERATIONAL_SUPPLIES:
                return new Sector(SectorEnum::WASH, $subSectorName);

            case SubSectorEnum::PROTECTION_PSYCHOSOCIAL_SUPPORT:
            case SubSectorEnum::INDIVIDUAL_PROTECTION_ASSISTANCE:
            case SubSectorEnum::COMMUNITY_BASED_INTERVENTIONS:
            case SubSectorEnum::PROTECTION_ADVOCACY:
            case SubSectorEnum::CHILD_PROTECTION:
            case SubSectorEnum::GENDER_BASED_VIOLENCE_ACTIVITIES:
                return new Sector(SectorEnum::PROTECTION, $subSectorName);

            case SubSectorEnum::TEACHER_INCENTIVE_PAYMENTS:
            case SubSectorEnum::TEACHER_TRAINING:
            case SubSectorEnum::LEARNING_MATERIALS:
            case SubSectorEnum::EDUCATION_PSYCHOSOCIAL_SUPPORT:
            case SubSectorEnum::EDUCATION_SERVICES:
                return new Sector(SectorEnum::EDUCATION, $subSectorName);

            default:
                return null;
        }
    }

    /**
     * @return Sector[]
     */
    public function findAll(): iterable
    {
        $sectors = [];
        foreach (SubSectorEnum::all() as $subSectorName) {
            $sectors[] = $this->findBySubSector($subSectorName);
        }
        return $sectors;
    }

    /**
     * @return Sector[]
     */
    public function getSubsBySector(): iterable
    {
        $sectors = [];
        foreach (SectorEnum::all() as $sector) {
            $sectors[$sector] = [];
        }
        /** @var Sector $subSectorName */
        foreach (SubSectorEnum::all() as $subSectorName) {
            $sectorDTO = $this->findBySubSector($subSectorName);
            $sectors[$sectorDTO->getSectorName()][] = $sectorDTO;
        }
        return $sectors;
    }
}
