<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CampAddress
 *
 * @ORM\Table(name="camp_address")
 * @ORM\Entity(repositoryClass="Repository\CampAddressRepository")
 */
class CampAddress
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="tentNumber", type="string", length=45)
     */
    private string $tentNumber;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Camp", cascade={"persist"})
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
     * @param Camp|null $camp
     *
     * @return CampAddress
     */
    public function setCamp(Camp $camp = null)
    {
        $this->camp = $camp;

        return $this;
    }

    /**
     * Get camp.
     *
     * @return Camp|null
     */
    public function getCamp()
    {
        return $this->camp;
    }
}