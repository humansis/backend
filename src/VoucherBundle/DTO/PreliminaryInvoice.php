<?php

namespace VoucherBundle\DTO;

use NewApiBundle\Entity\Project;

/**
 * stores statistics from repository
 */
class PreliminaryInvoice
{
    /** @var float */
    private $value;

    /** @var string */
    private $currency;

    /** @var string */
    private $projectName;

    /** @var int */
    private $projectId;

    /** @var int[] */
    private $purchasesIds;

    /** @var int */
    private $purchasesCount;

    /**
     * PurchaseBatchToRedeem constructor.
     *
     * @param mixed   $value
     * @param string  $currency
     * @param Project $project
     * @param int[]   $purchasesIds
     * @param int     $purchasesCount
     */
    public function __construct($value, string $currency, Project $project, array $purchasesIds, int $purchasesCount)
    {
        $this->value = $value;
        $this->purchasesIds = $purchasesIds;
        $this->currency = $currency;
        $this->projectName = $project->getName();
        $this->projectId = $project->getId();
        $this->purchasesCount = $purchasesCount;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int[]
     */
    public function getPurchasesIds(): array
    {
        return $this->purchasesIds;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->projectName;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->projectId;
    }

    /**
     * @return int
     */
    public function getPurchasesCount(): int
    {
        return $this->purchasesCount;
    }

}
