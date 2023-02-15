<?php

declare(strict_types=1);

namespace InputType;

use DateTime;
use DateTimeInterface;
use DBAL\SectorEnum;
use Enum\ProductCategoryType;
use Request\InputTypeInterface;
use Validator\Constraints\Country;
use Validator\Constraints\DateGreaterThan;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['ProjectUpdateInputType', 'Strict'])]
class ProjectUpdateInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $name; #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $internalId;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Country]
    private $iso3;

    #[Assert\Type('string')]
    private $notes;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $target;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Iso8601]
    private $startDate;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[DateGreaterThan(propertyPath: 'startDate', groups: ['Strict'])]
    #[Iso8601]
    private $endDate;

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [SectorEnum::class, "all"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, groups: ['Strict'])]
    private array $sectors = [];

    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private array $donorIds = [];

    #[Assert\All(
        constraints: [
            new Assert\Choice(callback: [ProductCategoryType::class, "values"], strict: true, groups: ['Strict']),
        ],
        groups: ['Strict']
    )]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, groups: ['Strict'])]
    private array $allowedProductCategoryTypes = [];

    #[Assert\Type('string')]
    #[Assert\NotNull]
    private ?string $projectInvoiceAddressLocal = '';

    #[Assert\Type('string')]
    #[Assert\NotNull]
    private ?string $projectInvoiceAddressEnglish = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getInternalId()
    {
        return $this->internalId;
    }

    /**
     * @param string $internalId
     */
    public function setInternalId($internalId)
    {
        $this->internalId = $internalId;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }

    /**
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return int
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param int $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return DateTimeInterface
     */
    public function getStartDate()
    {
        return new DateTime($this->startDate);
    }

    /**
     * @param string $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return DateTimeInterface
     */
    public function getEndDate()
    {
        return new DateTime($this->endDate);
    }

    /**
     * @param string $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return array|string[]
     */
    public function getSectors()
    {
        return $this->sectors;
    }

    /**
     * @param array $sectors
     */
    public function setSectors($sectors)
    {
        $this->sectors = $sectors;
    }

    /**
     * @return array|int[]
     */
    public function getDonorIds()
    {
        return $this->donorIds;
    }

    /**
     * @param array $donorIds
     */
    public function setDonorIds($donorIds)
    {
        $this->donorIds = $donorIds;
    }

    /**
     * @return string
     */
    public function getProjectInvoiceAddressLocal(): string
    {
        return $this->projectInvoiceAddressLocal;
    }

    public function setProjectInvoiceAddressLocal(?string $projectInvoiceAddressLocal): void
    {
        $this->projectInvoiceAddressLocal = $projectInvoiceAddressLocal ?: '';
    }

    /**
     * @return string
     */
    public function getProjectInvoiceAddressEnglish(): string
    {
        return $this->projectInvoiceAddressEnglish;
    }

    public function setProjectInvoiceAddressEnglish(?string $projectInvoiceAddressEnglish): void
    {
        $this->projectInvoiceAddressEnglish = $projectInvoiceAddressEnglish ?: '';
    }

    /**
     * @return string[]
     */
    public function getAllowedProductCategoryTypes(): array
    {
        return $this->allowedProductCategoryTypes;
    }

    /**
     * @param string[] $allowedProductCategoryTypes
     */
    public function setAllowedProductCategoryTypes(array $allowedProductCategoryTypes): void
    {
        $this->allowedProductCategoryTypes = $allowedProductCategoryTypes;
    }
}
