<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Phone
 *
 * @ORM\Table(name="phone")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\PhoneRepository")
 */
class Phone
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=45)
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=45)
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $prefix;

    /**
     * @var boolean
     *
     * @ORM\Column(name="proxy", type="boolean")
     * @Groups({"FullHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $proxy;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary", inversedBy="phones")
     */
    private $beneficiary;

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
     * Set number.
     *
     * @param string $number
     *
     * @return Phone
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set prefix.
     *
     * @param string $prefix
     *
     * @return Phone
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Phone
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set proxy.
     *
     * @param boolean $proxy
     *
     * @return Phone
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * Get proxy.
     *
     * @return boolean
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Set beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary|null $beneficiary
     *
     * @return Phone
     */
    public function setBeneficiary(\BeneficiaryBundle\Entity\Beneficiary $beneficiary = null)
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return \BeneficiaryBundle\Entity\Beneficiary|null
     */
    public function getBeneficiary()
    {
        return $this->beneficiary;
    }
}
