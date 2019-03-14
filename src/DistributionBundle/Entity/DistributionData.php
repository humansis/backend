<?php

namespace DistributionBundle\Entity;

use CommonBundle\Entity\Location;
use CommonBundle\Utils\ExportableInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Select;
use ProjectBundle\Entity\Project;
use JMS\Serializer\Annotation\Type as JMS_Type;
use JMS\Serializer\Annotation\Groups;

/**
 * DistributionData
 *
 * @ORM\Table(name="distribution_data")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\DistributionDataRepository")
 */
class DistributionData implements ExportableInterface
{

    const TYPE_BENEFICIARY = 0;
    const TYPE_HOUSEHOLD = 1;

    const NAME_HEADER_ID = "ID SYNC";

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"FullDistribution"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45)
     *
     * @Groups({"FullDistribution", "FullBooklet"})
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="UpdatedOn", type="datetime")
     * @JMS_Type("DateTime<'Y-m-d H:m:i'>")
     *
     * @Groups({"FullDistribution"})
     */
    private $updatedOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_distribution", type="date")
     * @JMS_Type("DateTime<'Y-m-d'>")
     *
     * @Groups({"FullDistribution"})
     */
    private $dateDistribution;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="CommonBundle\Entity\Location")
     *
     * @Groups({"FullDistribution"})
     */
    private $location;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project", inversedBy="distributions")
     *
     * @Groups({"FullDistribution"})
     */
    private $project;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\SelectionCriteria", mappedBy="distributionData")
     *
     * @Groups({"FullDistribution"})
     */
    private $selectionCriteria;

    /**
     * @var boolean
     *
     * @ORM\Column(name="archived", type="boolean", options={"default" : 0})
     *
     * @Groups({"FullDistribution"})
     */
    private $archived = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="validated", type="boolean", options={"default" : 0})
     *
     * @Groups({"FullDistribution"})
     */
    private $validated = 0;

    /**
     * @ORM\OneToMany(targetEntity="ReportingBundle\Entity\ReportingDistribution", mappedBy="distribution", cascade={"persist"})
     **/
    private $reportingDistribution;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="type_distribution")
     *
     * @Groups({"FullDistribution"})
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\Commodity", mappedBy="distributionData")
     * @Groups({"FullDistribution"})
     */
    private $commodities;

    /**
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\DistributionBeneficiary", mappedBy="distributionData")
     *
     * @Groups({"FullDistribution"})
     */
    private $distributionBeneficiaries;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reportingDistribution = new \Doctrine\Common\Collections\ArrayCollection();
        $this->selectionCriteria = new \Doctrine\Common\Collections\ArrayCollection();
        $this->distributionBeneficiaries = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setUpdatedOn(new \DateTime());
    }

    /**
     * Set id.
     *
     * @param $id
     * @return DistributionData
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set name.
     *
     * @param string $name
     *
     * @return DistributionData
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set updatedOn.
     *
     * @param \DateTime $updatedOn
     *
     * @return DistributionData
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn.
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return DistributionData
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Set validated.
     *
     * @param bool $validated
     *
     * @return DistributionData
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Get validated.
     *
     * @return bool
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return DistributionData
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set location.
     *
     * @param \CommonBundle\Entity\Location|null $location
     *
     * @return DistributionData
     */
    public function setLocation(\CommonBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \CommonBundle\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set project.
     *
     * @param \ProjectBundle\Entity\Project|null $project
     *
     * @return DistributionData
     */
    public function setProject(\ProjectBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project.
     *
     * @return \ProjectBundle\Entity\Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Add selectionCriterion.
     *
     * @param \DistributionBundle\Entity\SelectionCriteria $selectionCriterion
     *
     * @return DistributionData
     */
    public function addSelectionCriterion(\DistributionBundle\Entity\SelectionCriteria $selectionCriterion)
    {
        if (null === $this->selectionCriteria)
            $this->selectionCriteria = new \Doctrine\Common\Collections\ArrayCollection();
        $this->selectionCriteria[] = $selectionCriterion;

        return $this;
    }

    /**
     * Remove selectionCriterion.
     *
     * @param \DistributionBundle\Entity\SelectionCriteria $selectionCriterion
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeSelectionCriterion(\DistributionBundle\Entity\SelectionCriteria $selectionCriterion)
    {
        return $this->selectionCriteria->removeElement($selectionCriterion);
    }

    /**
     * Get selectionCriteria.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSelectionCriteria()
    {
        return $this->selectionCriteria;
    }

    /**
     * Add reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingDistribution $reportingDistribution
     *
     * @return DistributionData
     */
    public function addReportingDistribution(\ReportingBundle\Entity\ReportingDistribution $reportingDistribution)
    {
        $this->reportingDistribution[] = $reportingDistribution;

        return $this;
    }

    /**
     * Remove reportingDistribution.
     *
     * @param \ReportingBundle\Entity\ReportingDistribution $reportingDistribution
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeReportingDistribution(\ReportingBundle\Entity\ReportingDistribution $reportingDistribution)
    {
        return $this->reportingDistribution->removeElement($reportingDistribution);
    }

    /**
     * Get reportingDistribution.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReportingDistribution()
    {
        return $this->reportingDistribution;
    }

    /**
     * Add commodity.
     *
     * @param \DistributionBundle\Entity\Commodity $commodity
     *
     * @return DistributionData
     */
    public function addCommodity(\DistributionBundle\Entity\Commodity $commodity)
    {
        $this->commodities[] = $commodity;

        return $this;
    }

    /**
     * Remove commodity.
     *
     * @param \DistributionBundle\Entity\Commodity $commodity
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCommodity(\DistributionBundle\Entity\Commodity $commodity)
    {
        return $this->commodities->removeElement($commodity);
    }

    /**
     * Get commodities.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCommodities()
    {
        return $this->commodities;
    }

    /**
     * Add distributionBeneficiary.
     *
     * @param \DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary
     *
     * @return DistributionData
     */
    public function addDistributionBeneficiary(\DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary)
    {
        if (null === $this->distributionBeneficiaries)
            $this->distributionBeneficiaries = new \Doctrine\Common\Collections\ArrayCollection();
        $this->distributionBeneficiaries[] = $distributionBeneficiary;

        return $this;
    }

    /**
     * Remove distributionBeneficiary.
     *
     * @param \DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDistributionBeneficiary(\DistributionBundle\Entity\DistributionBeneficiary $distributionBeneficiary)
    {
        return $this->distributionBeneficiaries->removeElement($distributionBeneficiary);
    }

    /**
     * Get distributionBeneficiaries.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDistributionBeneficiaries()
    {
        return $this->distributionBeneficiaries;
    }

    /**
     * Set dateDistribution.
     *
     * @param \DateTime $dateDistribution
     *
     * @return DistributionData
     */
    public function setDateDistribution($dateDistribution)
    {
        $this->dateDistribution = $dateDistribution;

        return $this;
    }

    /**
     * Get dateDistribution.
     *
     * @return \DateTime
     */
    public function getDateDistribution()
    {
        return $this->dateDistribution;
    }


    function getMappedValueForExport(): array
    {
        // récuperer les criteria de selection  depuis l'objet selectioncriteria

        $valueselectioncriteria = [];
        foreach ($this->getSelectionCriteria() as $criterion) {
            $stringCriterion = $criterion->getFieldString() . " " . $criterion->getConditionString() . " " . $criterion->getValueString();
            array_push( $valueselectioncriteria, $stringCriterion);
        }
        $valueselectioncriteria = join(', ',  $valueselectioncriteria);

        // récuperer les valeurs des commodities depuis l'objet commodities

        $valuescommodities = [];
        
        foreach ($this->getCommodities() as $commodity) {
            $stringCommodity = $commodity->getModalityType()->getName() . " " . $commodity->getValue() . " " . $commodity->getUnit();
            array_push($valuescommodities, $stringCommodity);
        }
        $valuescommodities = join(',', $valuescommodities);


        //récuperer les valeurs des distributions des beneficiaires depuis l'objet distribution
        // $valuesdistributionbeneficiaries = [];

        // foreach ($this->getDistributionBeneficiaries() as $value) {
        //     array_push($valuesdistributionbeneficiaries, $value->getIdNumber());
        // }
        // $valuesdistributionbeneficiaries = join(',',$valuesdistributionbeneficiaries);

        $typeString = $this->getType() === self::TYPE_BENEFICIARY ? 'Beneficiaries' : 'Households';


        // récuperer les adm1 , adm2 , adm3 , adm 4 depuis l'objet localisation : faut vérifier d'abord s'ils sont null ou pas pour avoir le nom

        $adm1 = ( ! empty($this->getLocation()->getAdm1()) ) ? $this->getLocation()->getAdm1()->getName() : '';
        $adm2 = ( ! empty($this->getLocation()->getAdm2()) ) ? $this->getLocation()->getAdm2()->getName() : '';
        $adm3 = ( ! empty($this->getLocation()->getAdm3()) ) ? $this->getLocation()->getAdm3()->getName() : '';
        $adm4 = ( ! empty($this->getLocation()->getAdm4()) ) ? $this->getLocation()->getAdm4()->getName() : '';

        return [
            "projet" => $this->getProject()->getName(),
            "type" => $typeString,
            // "Archived"=> $this->getArchived(),
            "adm1" => $adm1,
            "adm2" =>$adm2,
            "adm3" =>$adm3,
            "adm4" =>$adm4,
            "Name" => $this->getName(),
            "Date of distribution " => $this->getDateDistribution(),
            "Update on " => $this->getUpdatedOn(),
            "Selection criteria" =>  $valueselectioncriteria,
            "Commodities " => $valuescommodities,
            // "Distribution beneficiaries" =>$valuesdistributionbeneficiaries,
        ];
    }
}
