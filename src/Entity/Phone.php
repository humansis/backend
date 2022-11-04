<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Phone
 *
 * @ORM\Table(name="phone")
 * @ORM\Entity(repositoryClass="Repository\PhoneRepository")
 */
class Phone
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="number", type="string", length=45)
     */
    private string $number;

    /**
     * @ORM\Column(name="type", type="string", length=45, nullable=true)
     */
    private ?string $type = null;

    /**
     * @ORM\Column(name="prefix", type="string", length=45)
     */
    private string $prefix;

    /**
     * @ORM\Column(name="proxy", type="boolean")
     */
    private bool $proxy = false;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Person", inversedBy="phones")
     */
    private ?\Entity\Person $person = null;

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
     *
     */
    public function setType(?string $type): Phone
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
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
