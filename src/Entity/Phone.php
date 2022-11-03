<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Phone
 *
 * @ORM\Table(name="phone")
 * @ORM\Entity(repositoryClass="Repository\PhoneRepository")
 */
class Phone
{
    use StandardizedPrimaryKey;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=45)
     */
    private $number;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="string", length=45, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="prefix", type="string", length=45)
     */
    private $prefix;

    /**
     * @var bool
     *
     * @ORM\Column(name="proxy", type="boolean")
     */
    private $proxy = false;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Entity\Person", inversedBy="phones")
     */
    private $person;

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
     * @param string|null $type
     *
     * @return Phone
     */
    public function setType(?string $type): Phone
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set proxy.
     *
     * @param bool $proxy
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
     * @return bool
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Set beneficiary.
     *
     * @param Person|null $person
     *
     * @return Phone
     */
    public function setPerson(?Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return Person|null
     */
    public function getPerson()
    {
        return $this->person;
    }
}
