<?php

declare(strict_types=1);

namespace Utils;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Serializer\Serializer;
use Entity\Project;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use InvalidArgumentException;
use Component\Codelist\CodeItem;
use Entity\ProjectSector;
use Services\CodeListService;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use DTO\Sector;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SectorService
 *
 * @package Utils
 */
class SectorService
{
    /**
     * SectorService constructor.
     */
    public function __construct(private readonly CodeListService $codeListService)
    {
    }

    /**
     * @return CodeItem[]
     */
    public function getSectorsInProject(Project $project): array
    {
        $data = [];
        /** @var ProjectSector $sector */
        foreach ($project->getSectors() as $sector) {
            $data[] = $sector->getSector();
        }

        return $this->codeListService->mapEnum($data);
    }

    public function findBySubSector(string $subSectorName): ?Sector
    {
        $sector = $this->findSector($subSectorName);
        if (!$sector) {
            return null;
        }
        return match ($sector->getSubSectorName()) {
            SubSectorEnum::IN_KIND_FOOD, SubSectorEnum::CASH_TRANSFERS, SubSectorEnum::FOOD_VOUCHERS, SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE, SubSectorEnum::HYGIENE_KITS => $sector->setDistributionAllowed()
                ->setHouseholdAllowed()
                ->setBeneficiaryAllowed(),
            SubSectorEnum::FOOD_CASH_FOR_WORK, SubSectorEnum::SKILLS_TRAINING, SubSectorEnum::LIVELIHOOD_CASH_FOR_WORK, SubSectorEnum::PROTECTION_PSYCHOSOCIAL_SUPPORT, SubSectorEnum::INDIVIDUAL_PROTECTION_ASSISTANCE, SubSectorEnum::TEACHER_TRAINING, SubSectorEnum::EDUCATION_PSYCHOSOCIAL_SUPPORT, SubSectorEnum::LEARNING_SUPPORT, SubSectorEnum::EDUCATION_CASH_FOR_WORK, SubSectorEnum::PARENT_SESSIONS => $sector->setActivityAllowed()
                ->setBeneficiaryAllowed(),
            SubSectorEnum::TECHNICAL_SUPPORT => $sector->setActivityAllowed()
                ->setHouseholdAllowed()
                ->setBeneficiaryAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::PROVISION_OF_INPUTS, SubSectorEnum::CASH_FOR_WINTERIZATION, SubSectorEnum::CASH_FOR_PROTECTION => $sector->setDistributionAllowed()
                ->setHouseholdAllowed()
                ->setBeneficiaryAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::BUSINESS_GRANTS, SubSectorEnum::LEARNING_MATERIALS => $sector->setDistributionAllowed()
                ->setBeneficiaryAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::AGRICULTURAL_VOUCHERS => $sector->setDistributionAllowed()
                ->setHouseholdAllowed()
                ->setBeneficiaryAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::REHABILITATION, SubSectorEnum::CONSTRUCTION => $sector->setActivityAllowed()
                ->setInstitutionAllowed()
                ->setCommunityAllowed()
                ->setHouseholdAllowed(),
            SubSectorEnum::SETTLEMENT_UPGRADES => $sector->setActivityAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::WINTERIZATION_KITS, SubSectorEnum::SHELTER_KITS, SubSectorEnum::NFI_KITS, SubSectorEnum::CASH_FOR_SHELTER => $sector->setDistributionAllowed()
                ->setHouseholdAllowed(),
            SubSectorEnum::WINTERIZATION_UPGRADES => $sector->setActivityAllowed()
                ->setHouseholdAllowed(),
            SubSectorEnum::WATER_POINT_REHABILITATION, SubSectorEnum::WATER_POINT_CONSTRUCTION, SubSectorEnum::WATER_TRUCKING, SubSectorEnum::WATER_TREATMENT, SubSectorEnum::VECTOR_CONTROL, SubSectorEnum::SOLID_WASTE_MANAGEMENT, SubSectorEnum::SANITATION => $sector->setActivityAllowed()
                ->setHouseholdAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::HYGIENE_PROMOTION => $sector->setDistributionAllowed()
                ->setHouseholdAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::OPERATIONAL_SUPPLIES => $sector->setDistributionAllowed()
                ->setHouseholdAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::COMMUNITY_BASED_INTERVENTIONS => $sector->setActivityAllowed()
                ->setCommunityAllowed(),
            SubSectorEnum::PROTECTION_ADVOCACY => $sector->setDistributionAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::CHILD_PROTECTION => $sector->setActivityAllowed()
                ->setBeneficiaryAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::GENDER_BASED_VIOLENCE_ACTIVITIES => $sector->setActivityAllowed()
                ->setBeneficiaryAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed()
                ->setHouseholdAllowed(),
            SubSectorEnum::TEACHER_INCENTIVE_PAYMENTS => $sector->setDistributionAllowed()
                ->setBeneficiaryAllowed(),
            SubSectorEnum::DEFAULT_EMERGENCY_TELCO, SubSectorEnum::DEFAULT_HEALTH, SubSectorEnum::DEFAULT_LOGISTICS, SubSectorEnum::DEFAULT_NUTRITION, SubSectorEnum::DEFAULT_MINE, SubSectorEnum::DEFAULT_DRR_RESILIENCE, SubSectorEnum::DEFAULT_NON_SECTOR, SubSectorEnum::DEFAULT_CAMP_MANAGEMENT, SubSectorEnum::DEFAULT_EARLY_RECOVERY => $sector->setActivityAllowed()
                ->setDistributionAllowed()
                ->setBeneficiaryAllowed()
                ->setHouseholdAllowed()
                ->setCommunityAllowed()
                ->setInstitutionAllowed(),
            SubSectorEnum::SCHOOL_OPERATIONAL_SUPPORT => $sector->setActivityAllowed()
                ->setInstitutionAllowed(),
            default => null,
        };
    }

    private function findSector(string $subSectorName): ?Sector
    {
        return match ($subSectorName) {
            SubSectorEnum::IN_KIND_FOOD, SubSectorEnum::CASH_TRANSFERS, SubSectorEnum::FOOD_VOUCHERS, SubSectorEnum::FOOD_CASH_FOR_WORK => new Sector(SectorEnum::FOOD_SECURITY, $subSectorName),
            SubSectorEnum::SKILLS_TRAINING, SubSectorEnum::TECHNICAL_SUPPORT, SubSectorEnum::PROVISION_OF_INPUTS, SubSectorEnum::BUSINESS_GRANTS, SubSectorEnum::AGRICULTURAL_VOUCHERS, SubSectorEnum::LIVELIHOOD_CASH_FOR_WORK => new Sector(SectorEnum::LIVELIHOODS, $subSectorName),
            SubSectorEnum::MULTI_PURPOSE_CASH_ASSISTANCE => new Sector(SectorEnum::MULTIPURPOSE_CASH, $subSectorName),
            SubSectorEnum::REHABILITATION, SubSectorEnum::CONSTRUCTION, SubSectorEnum::SETTLEMENT_UPGRADES, SubSectorEnum::WINTERIZATION_KITS, SubSectorEnum::WINTERIZATION_UPGRADES, SubSectorEnum::SHELTER_KITS, SubSectorEnum::NFI_KITS, SubSectorEnum::CASH_FOR_SHELTER, SubSectorEnum::CASH_FOR_WINTERIZATION => new Sector(SectorEnum::SHELTER, $subSectorName),
            SubSectorEnum::WATER_POINT_REHABILITATION, SubSectorEnum::WATER_POINT_CONSTRUCTION, SubSectorEnum::WATER_TRUCKING, SubSectorEnum::WATER_TREATMENT, SubSectorEnum::VECTOR_CONTROL, SubSectorEnum::SOLID_WASTE_MANAGEMENT, SubSectorEnum::SANITATION, SubSectorEnum::HYGIENE_PROMOTION, SubSectorEnum::HYGIENE_KITS, SubSectorEnum::OPERATIONAL_SUPPLIES => new Sector(SectorEnum::WASH, $subSectorName),
            SubSectorEnum::PROTECTION_PSYCHOSOCIAL_SUPPORT, SubSectorEnum::INDIVIDUAL_PROTECTION_ASSISTANCE, SubSectorEnum::COMMUNITY_BASED_INTERVENTIONS, SubSectorEnum::PROTECTION_ADVOCACY, SubSectorEnum::CHILD_PROTECTION, SubSectorEnum::GENDER_BASED_VIOLENCE_ACTIVITIES, SubSectorEnum::CASH_FOR_PROTECTION => new Sector(SectorEnum::PROTECTION, $subSectorName),
            SubSectorEnum::TEACHER_INCENTIVE_PAYMENTS, SubSectorEnum::TEACHER_TRAINING, SubSectorEnum::LEARNING_MATERIALS, SubSectorEnum::EDUCATION_PSYCHOSOCIAL_SUPPORT, SubSectorEnum::LEARNING_SUPPORT, SubSectorEnum::EDUCATION_CASH_FOR_WORK, SubSectorEnum::PARENT_SESSIONS, SubSectorEnum::SCHOOL_OPERATIONAL_SUPPORT => new Sector(SectorEnum::EDUCATION_TVET, $subSectorName),
            SubSectorEnum::DEFAULT_EMERGENCY_TELCO => new Sector(SectorEnum::EMERGENCY_TELCO, $subSectorName),
            SubSectorEnum::DEFAULT_HEALTH => new Sector(SectorEnum::HEALTH, $subSectorName),
            SubSectorEnum::DEFAULT_LOGISTICS => new Sector(SectorEnum::LOGISTICS, $subSectorName),
            SubSectorEnum::DEFAULT_NUTRITION => new Sector(SectorEnum::NUTRITION, $subSectorName),
            SubSectorEnum::DEFAULT_MINE => new Sector(SectorEnum::MINE, $subSectorName),
            SubSectorEnum::DEFAULT_DRR_RESILIENCE => new Sector(SectorEnum::DRR_RESILIENCE, $subSectorName),
            SubSectorEnum::DEFAULT_NON_SECTOR => new Sector(SectorEnum::NON_SECTOR, $subSectorName),
            SubSectorEnum::DEFAULT_CAMP_MANAGEMENT => new Sector(SectorEnum::CAMP_MANAGEMENT, $subSectorName),
            SubSectorEnum::DEFAULT_EARLY_RECOVERY => new Sector(SectorEnum::EARLY_RECOVERY, $subSectorName),
            default => null,
        };
    }

    public function findTargetsByType(string $type): array
    {
        if (!in_array($type, AssistanceType::values())) {
            throw new InvalidArgumentException('This assistence type is not supported');
        }

        $assistanceTargets = [];

        foreach (SubSectorEnum::all() as $subSectorName) {
            /** @var Sector $sector */
            $sector = $this->findBySubSector($subSectorName);
            if ($sector && $sector->isAssistanceTypeAllowed($type)) {
                foreach (AssistanceTargetType::values() as $targetType) {
                    if ($sector->isAssistanceTargetAllowed($targetType)) {
                        $assistanceTargets[] = $targetType;
                    }
                }
            }
        }

        return array_unique($assistanceTargets);
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

    /**
     * @return Sector[]
     * @throws InvalidArgumentException
     */
    public function findSubsSectorsBySector(string $sector): array
    {
        $sectors = $this->getSubsBySector();

        if (!isset($sectors[$sector])) {
            throw new InvalidArgumentException('Sector not found');
        }

        return $sectors[$sector];
    }
}
