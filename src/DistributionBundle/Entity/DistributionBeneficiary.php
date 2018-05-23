<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DistributionBeneficiary
 *
 * @ORM\Table(name="distribution_beneficiary")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\DistributionBeneficiaryRepository")
 */
class DistributionBeneficiary
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
     * @var DistributionData
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\DistributionData")
     */
    private $distributionData;

    /**
     * @var ProjectBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\ProjectBeneficiary")
     */
    private $projectBeneficiary;

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
     * Set distributionData.
     *
     * @param \DistributionBundle\Entity\DistributionData|null $distributionData
     *
     * @return DistributionBeneficiary
     */
    public function setDistributionData(\DistributionBundle\Entity\DistributionData $distributionData = null)
    {
        $this->distributionData = $distributionData;

        return $this;
    }

    /**
     * Get distributionData.
     *
     * @return \DistributionBundle\Entity\DistributionData|null
     */
    public function getDistributionData()
    {
        return $this->distributionData;
    }

    /**
     * Set projectBeneficiary.
     *
     * @param \DistributionBundle\Entity\ProjectBeneficiary|null $projectBeneficiary
     *
     * @return DistributionBeneficiary
     */
    public function setProjectBeneficiary(\DistributionBundle\Entity\ProjectBeneficiary $projectBeneficiary = null)
    {
        $this->projectBeneficiary = $projectBeneficiary;

        return $this;
    }

    /**
     * Get projectBeneficiary.
     *
     * @return \DistributionBundle\Entity\ProjectBeneficiary|null
     */
    public function getProjectBeneficiary()
    {
        return $this->projectBeneficiary;
    }
}
