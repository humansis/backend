<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Read only entity.
 *
 * @ORM\MappedSuperclass(repositoryClass="NewApiBundle\Repository\AssistanceStatisticsRepository")
 */
class AssistanceStatistics
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="numberOfBeneficiaries", type="integer")
     */
    private $numberOfBeneficiaries;

    /**
     * @var float
     * @ORM\Column(name="summaryOfTotalItems", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $summaryOfTotalItems;

    /**
     * @var float
     * @ORM\Column(name="summaryOfDistributedItems", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $summaryOfDistributedItems;

    /**
     * @var float|null
     * @ORM\Column(name="summaryOfUsedItems", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $summaryOfUsedItems;

    protected function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getNumberOfBeneficiaries(): int
    {
        return $this->numberOfBeneficiaries;
    }

    /**
     * @return float
     */
    public function getSummaryOfTotalItems(): float
    {
        return (float) $this->summaryOfTotalItems;
    }

    /**
     * @return float
     */
    public function getSummaryOfDistributedItems(): float
    {
        return (float) $this->summaryOfDistributedItems;
    }

    /**
     * @return float|null
     */
    public function getSummaryOfUsedItems(): ?float
    {
        return null === $this->summaryOfUsedItems ? null : (float) $this->summaryOfUsedItems;
    }
}
