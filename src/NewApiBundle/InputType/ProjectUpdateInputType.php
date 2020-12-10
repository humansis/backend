<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Validator\Constraints\DateGreaterThan;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectUpdateInputType implements \CommonBundle\InputType\InputTypeInterface
{
    /**
     * @var string
     * @Assert\LessThanOrEqual(255)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $name; // todo check unique name

    /**
     * @var string|null
     * @Assert\LessThanOrEqual(255)
     */
    private $internalId;

    /**
     * @var string
     * @Assert\Choice({"KHM", "SYR", "UKR", "ETH"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $iso3;

    /**
     * @var string|null
     */
    private $notes;

    /**
     * @var int
     * @Assert\GreaterThan(0)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $target;

    /**
     * @var string
     * @Assert\Date
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $startDate;

    /**
     * @var string
     * @Assert\Date
     * @Assert\NotBlank
     * @Assert\NotNull
     * @DateGreaterThan(propertyPath="startDate")
     */
    private $endDate;

    /**
     * @var array
     * @Assert\Count(min=1)
     * @Assert\All(
     *     @Assert\Choice(callback={"ProjectBundle\DBAL\SectorEnum", "all"})
     * )
     */
    private $sectors = [];

    /**
     * @var array
     * @Assert\All(
     *     @Assert\Type("integer")
     * )
     */
    private $donorIds = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    /**
     * @param string $internalId
     */
    public function setInternalId(?string $internalId): void
    {
        $this->internalId = $internalId;
    }

    /**
     * @return string
     */
    public function getIso3(): string
    {
        return $this->iso3;
    }

    /**
     * @param string $iso3
     */
    public function setIso3(string $iso3): void
    {
        $this->iso3 = $iso3;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return int
     */
    public function getTarget(): int
    {
        return $this->target;
    }

    /**
     * @param int $target
     */
    public function setTarget(int $target): void
    {
        $this->target = $target;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartDate(): \DateTimeInterface
    {
        return new \DateTime($this->startDate);
    }

    /**
     * @param string $startDate
     */
    public function setStartDate(string $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getEndDate(): \DateTimeInterface
    {
        return new \DateTime($this->endDate);
    }

    /**
     * @param string $endDate
     */
    public function setEndDate(string $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @return array|string[]
     */
    public function getSectors(): array
    {
        return $this->sectors;
    }

    /**
     * @param array $sectors
     */
    public function setSectors(array $sectors): void
    {
        $this->sectors = $sectors;
    }

    /**
     * @return array|int[]
     */
    public function getDonorIds(): array
    {
        return $this->donorIds;
    }

    /**
     * @param array $donorIds
     */
    public function setDonorIds(array $donorIds): void
    {
        $this->donorIds = $donorIds;
    }
}
