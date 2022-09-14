<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * CampAddress
 *
 * @ORM\Table(name="camp_address")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CampAddressRepository")
 */
class CampAddress extends AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="tentNumber", type="string", length=45)
     * @SymfonyGroups({"FullHousehold"})
     */
    private $tentNumber;

     /**
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Camp", cascade={"persist"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold"})
     */
    private $camp;


    /**
     * Set tentNumber.
     *
     * @param string $tentNumber
     *
     * @return CampAddress
     */
    public function setTentNumber($tentNumber)
    {
        $this->tentNumber = $tentNumber;

        return $this;
    }

    /**
     * Get tentNumber.
     *
     * @return string
     */
    public function getTentNumber()
    {
        return $this->tentNumber;
    }

    /**
     * Set camp.
     *
     * @param \BeneficiaryBundle\Entity\Camp|null $camp
     *
     * @return CampAddress
     */
    public function setCamp(\BeneficiaryBundle\Entity\Camp $camp = null)
    {
        $this->camp = $camp;

        return $this;
    }

    /**
     * Get camp.
     *
     * @return \BeneficiaryBundle\Entity\Camp|null
     */
    public function getCamp()
    {
        return $this->camp;
    }
}
