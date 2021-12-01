<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Enum\SelectionCriteriaField;
use NewApiBundle\InputType\Assistance\CommodityInputType;
use NewApiBundle\InputType\Assistance\SelectionCriterionInputType;
use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\Country;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"AssistanceCreateInputType", "Strict", "AdditionalChecks"})
 */
class AssistanceCreateInputType implements InputTypeInterface
{
    /**
     * @Assert\NotBlank
     * @Assert\NotNull
     * @Country
     */
    private $iso3;

    /**
     * @Iso8601
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $dateDistribution;

    /**
     * @Iso8601
     */
    private $dateExpiration;

    /**
     * @Assert\Type("string")
     */
    private $description;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $projectId;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $locationId;

    /**
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceTargetType", "values"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $target;

    /**
     * @Assert\Choice(callback={"DistributionBundle\Enum\AssistanceType", "values"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $type;

    /**
     * @Assert\Choice(callback={"ProjectBundle\DBAL\SectorEnum", "all"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $sector;

    /**
     * @Assert\Choice(callback={"ProjectBundle\DBAL\SubSectorEnum", "all"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $subsector;

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    private $commodities = [];

    /**
     * @Assert\Type("array")
     * @Assert\Valid
     */
    private $selectionCriteria = [];

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $threshold;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $communities;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $institutions;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $householdsTargeted;

    /**
     * @Assert\Type("integer")
     * @Assert\GreaterThanOrEqual("0")
     */
    private $individualsTargeted;

    /**
     * @Assert\Type("boolean")
     */
    private $completed = false;

    /**
     * @Assert\Type("boolean")
     */
    private $validated = false;

    /**
     * @Assert\Type("numeric")
     */
    private $foodLimit;

    /**
     * @Assert\Type("numeric")
     */
    private $nonFoodLimit;

    /**
     * @Assert\Type("numeric")
     */
    private $cashbackLimit;

    /**
     * @Assert\Type("boolean")
     */
    private $remoteDistributionAllowed;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"NewApiBundle\Enum\ProductCategoryType", "values"}, strict=true, groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    private $allowedProductCategoryTypes;

    /**
     * @Assert\IsTrue(groups="AdditionalChecks", message="Please add BNF has valid card criterion for each group")
     * @return bool
     */
    public function isValidSmartcardForRemoteDistribution(): bool
    {
        if ($this->remoteDistributionAllowed) {
            $validSmartcardFieldsCount = 0;
            $hasValidSmartcardField = false;

            /** @var SelectionCriterionInputType $criteriaField */
            foreach ($this->selectionCriteria as $key => $criteriaField) {
                if ($criteriaField->getField() === SelectionCriteriaField::HAS_VALID_SMARTCARD) {
                    if ($criteriaField->getValue() === true) {
                        if ($validSmartcardFieldsCount > 0 && $hasValidSmartcardField === false) {
                            $hasValidSmartcardField = false;
                        } else {
                            $hasValidSmartcardField = true;
                        }
                    } else {
                        $hasValidSmartcardField = false;
                    }
                }
                $validSmartcardFieldsCount++;
            }

            return $hasValidSmartcardField;
        }

        return true;
    }

    /**
     * @Assert\IsTrue(groups="AdditionalChecks", message="remoteDistributionAllowed must not be null if distribution is for smartcards. Null otherwise.")
     */
    public function isNotNullRemoteDistributionWhenSmartcard(): bool
    {
        /** @var CommodityInputType $commodity */
        foreach ($this->commodities as $commodity) {
            if ($commodity->getModalityType() === 'Smartcard') { //TODO modality type enum
                return $this->remoteDistributionAllowed !== null;
            }
        }

        return $this->remoteDistributionAllowed === null;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * @param string $iso3
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateDistribution()
    {
        return $this->dateDistribution;
    }

    /**
     * @param \DateTimeInterface $dateDistribution
     */
    public function setDateDistribution($dateDistribution)
    {
        $this->dateDistribution = $dateDistribution;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateExpiration()
    {
        return $this->dateExpiration;
    }

    /**
     * @param \DateTimeInterface|null $dateExpiration
     */
    public function setDateExpiration($dateExpiration): void
    {
        $this->dateExpiration = $dateExpiration;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param int $locationId
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * @param string $sector
     */
    public function setSector($sector)
    {
        $this->sector = $sector;
    }

    /**
     * @return string
     */
    public function getSubsector()
    {
        return $this->subsector;
    }

    /**
     * @param string $subsector
     */
    public function setSubsector($subsector)
    {
        $this->subsector = $subsector;
    }

    /**
     * @return Assistance\CommodityInputType[]
     */
    public function getCommodities()
    {
        return $this->commodities;
    }

    /**
     * @param Assistance\CommodityInputType $commodity
     */
    public function addCommodity(Assistance\CommodityInputType $commodity)
    {
        $this->commodities[] = $commodity;
    }

    public function removeCommodity($commodity)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return Assistance\SelectionCriterionInputType[]
     */
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }

    /**
     * @param Assistance\SelectionCriterionInputType $selectionCriterion
     */
    public function addSelectionCriterion(Assistance\SelectionCriterionInputType $selectionCriterion)
    {
        $this->selectionCriteria[] = $selectionCriterion;
    }

    public function removeSelectionCriterion($nationalIdCard)
    {
        // method must be declared to fullfill normalizer requirements
    }

    /**
     * @return int|null
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param int|null $threshold
     */
    public function setThreshold($threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * @return bool
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @param bool $completed
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    /**
     * @return int[]|null
     */
    public function getCommunities()
    {
        return $this->communities;
    }

    /**
     * @param int[]|null $communities
     */
    public function setCommunities($communities)
    {
        $this->communities = $communities;
    }

    /**
     * @return int[]|null
     */
    public function getInstitutions()
    {
        return $this->institutions;
    }

    /**
     * @param int[]|null $institutions
     */
    public function setInstitutions($institutions)
    {
        $this->institutions = $institutions;
    }

    /**
     * @return int|null
     */
    public function getHouseholdsTargeted()
    {
        return $this->householdsTargeted;
    }

    /**
     * @param int|null $householdsTargeted
     */
    public function setHouseholdsTargeted($householdsTargeted)
    {
        $this->householdsTargeted = $householdsTargeted;
    }

    /**
     * @return int|null
     */
    public function getIndividualsTargeted()
    {
        return $this->individualsTargeted;
    }

    /**
     * @param int|null $individualsTargeted
     */
    public function setIndividualsTargeted($individualsTargeted)
    {
        $this->individualsTargeted = $individualsTargeted;
    }

    /**
     * @return bool
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * @param bool $validated
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;
    }

    /**
     * @return mixed
     */
    public function getFoodLimit()
    {
        return $this->foodLimit;
    }

    /**
     * @param mixed $foodLimit
     */
    public function setFoodLimit($foodLimit): void
    {
        $this->foodLimit = $foodLimit;
    }

    /**
     * @return mixed
     */
    public function getNonFoodLimit()
    {
        return $this->nonFoodLimit;
    }

    /**
     * @param mixed $nonFoodLimit
     */
    public function setNonFoodLimit($nonFoodLimit): void
    {
        $this->nonFoodLimit = $nonFoodLimit;
    }

    /**
     * @return mixed
     */
    public function getCashbackLimit()
    {
        return $this->cashbackLimit;
    }

    /**
     * @param mixed $cashbackLimit
     */
    public function setCashbackLimit($cashbackLimit): void
    {
        $this->cashbackLimit = $cashbackLimit;
    }

    /**
     * @return bool|null
     */
    public function getRemoteDistributionAllowed()
    {
        return $this->remoteDistributionAllowed;
    }

    /**
     * @param bool|null $remoteDistributionAllowed
     */
    public function setRemoteDistributionAllowed($remoteDistributionAllowed)
    {
        $this->remoteDistributionAllowed = $remoteDistributionAllowed;
    }

    /**
     * @return array
     */
    public function getAllowedProductCategoryTypes()
    {
        return $this->allowedProductCategoryTypes;
    }

    /**
     * @param array $allowedProductCategoryTypes
     */
    public function setAllowedProductCategoryTypes($allowedProductCategoryTypes): void
    {
        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;
    }

}
