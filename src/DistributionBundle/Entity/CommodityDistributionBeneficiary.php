<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommodityDistributionBeneficiary
 *
 * @ORM\Table(name="commodity_distribution_beneficiary")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\CommodityDistributionBeneficiaryRepository")
 */
class CommodityDistributionBeneficiary
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var DistributionBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionBeneficiary")
     */
    private $distributionBeneficiary;

    /**
     * @var Commodity
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Commodity")
     */
    private $commodity;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return CommodityDistributionBeneficiary
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set distributionBeneficiary.
     *
     * @param \DistributionBundle\Entity\DistributionBeneficiary|null $distributionBeneficiary
     *
     * @return CommodityDistributionBeneficiary
     */
    public function setDistributionBeneficiary(\DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary = null)
    {
        $this->distributionBeneficiary = $distributionBeneficiary;

        return $this;
    }

    /**
     * Get distributionBeneficiary.
     *
     * @return \DistributionBundle\Entity\DistributionBeneficiary|null
     */
    public function getDistributionBeneficiary()
    {
        return $this->distributionBeneficiary;
    }

    /**
     * Set commodity.
     *
     * @param \DistributionBundle\Entity\Commodity|null $commodity
     *
     * @return CommodityDistributionBeneficiary
     */
    public function setCommodity(\DistributionBundle\Entity\Commodity $commodity = null)
    {
        $this->commodity = $commodity;

        return $this;
    }

    /**
     * Get commodity.
     *
     * @return \DistributionBundle\Entity\Commodity|null
     */
    public function getCommodity()
    {
        return $this->commodity;
    }
}
