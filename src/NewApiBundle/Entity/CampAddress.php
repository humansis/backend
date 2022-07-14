<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CampAddress
 *
 * @ORM\Table(name="camp_address")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\CampAddressRepository")
 */
class CampAddress
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
     * @ORM\Column(name="tentNumber", type="string", length=45)
     */
    private $tentNumber;

     /**
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Camp", cascade={"persist"})
     */
    private $camp;


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
     * @param \NewApiBundle\Entity\Camp|null $camp
     *
     * @return CampAddress
     */
    public function setCamp(\NewApiBundle\Entity\Camp $camp = null)
    {
        $this->camp = $camp;

        return $this;
    }

    /**
     * Get camp.
     *
     * @return \NewApiBundle\Entity\Camp|null
     */
    public function getCamp()
    {
        return $this->camp;
    }
}
