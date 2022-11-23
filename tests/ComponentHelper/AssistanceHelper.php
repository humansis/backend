<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use Component\Assistance\AssistanceFactory;
use Component\Assistance\Domain\Assistance;
use DateTimeImmutable;
use DBAL\SectorEnum;
use DBAL\SubSectorEnum;
use Doctrine\ORM\EntityManagerInterface;
use Entity\Location;
use Entity\Project;
use Enum\AssistanceTargetType;
use Enum\AssistanceType;
use Enum\ProductCategoryType;
use Exception;
use InputType\Assistance\CommodityInputType;
use InputType\Assistance\SelectionCriterionInputType;
use InputType\AssistanceCreateInputType;
use Symfony\Component\DependencyInjection\Container;
use Utils\AssistanceService;

/**
 * @property Container $container
 * @property EntityManagerInterface $em
 */
trait AssistanceHelper
{
    /**
     * @throws Exception
     */
    public function createAssistance(
        AssistanceCreateInputType $assistanceCreateInputType,
        AssistanceFactory $assistanceFactory,
    ): Assistance {
        $assistance = $assistanceFactory->create($assistanceCreateInputType);
        $this->em->getRepository(\Entity\Assistance::class)->save($assistance);

        return $assistance;
    }

    public static function buildSelectionCriteriaInputType(): SelectionCriterionInputType
    {
        $selectionCriteriaType = new SelectionCriterionInputType();
        $selectionCriteriaType->setCondition('=');
        $selectionCriteriaType->setField('gender');
        $selectionCriteriaType->setTarget('Beneficiary');
        $selectionCriteriaType->setGroup(0);
        $selectionCriteriaType->setWeight(1);
        $selectionCriteriaType->setValue('1');

        return $selectionCriteriaType;
    }

    public static function buildCommoditiesType(
        string $currency,
        string $modalityType,
        float $value
    ): CommodityInputType {
        $commodityType = new CommodityInputType();
        $commodityType->setModalityType($modalityType);
        $commodityType->setUnit($currency);
        $commodityType->setValue($value);

        return $commodityType;
    }

    /**
     * @param CommodityInputType[]|null $commodityInputTypes
     * @param SelectionCriterionInputType[]|null $selectionCriteriaInputTypes
     */
    public static function buildAssistanceInputType(
        Project $project,
        Location $location,
        ?array $commodityInputTypes = null,
        ?array $selectionCriteriaInputTypes = null
    ): AssistanceCreateInputType {
        $expirationDate = DateTimeImmutable::createFromMutable($project->getEndDate());
        $assistanceInputType = new AssistanceCreateInputType();
        $assistanceInputType->setIso3($project->getCountryIso3());
        $assistanceInputType->setDateDistribution($expirationDate->modify('-2 Days')->format('Y-m-d'));
        $assistanceInputType->setDateExpiration($expirationDate->modify('-1 Day')->format('Y-m-d'));
        $assistanceInputType->setProjectId($project->getId());
        $assistanceInputType->setLocationId($location->getId());
        $assistanceInputType->setTarget(AssistanceTargetType::INDIVIDUAL);
        $assistanceInputType->setType(AssistanceType::DISTRIBUTION);
        $assistanceInputType->setSector(SectorEnum::FOOD_SECURITY);
        $assistanceInputType->setSubsector(SubSectorEnum::CASH_TRANSFERS);
        $assistanceInputType->setAllowedProductCategoryTypes([ProductCategoryType::FOOD]);
        $assistanceInputType->setFoodLimit(15);

        if ($commodityInputTypes) {
            foreach ($commodityInputTypes as $commodityInputType) {
                $assistanceInputType->addCommodity($commodityInputType);
            }
        }
        if ($selectionCriteriaInputTypes) {
            foreach ($selectionCriteriaInputTypes as $selectionCriterionInputType) {
                $assistanceInputType->addSelectionCriterion($selectionCriterionInputType);
            }
        }

        return $assistanceInputType;
    }
}
