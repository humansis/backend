<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CountrySpecific
 *
 * @ORM\Table(name="country_specific")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\CountrySpecificRepository")
 */
class CountrySpecific
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
     * @ORM\Column(name="field", type="string", length=45)
     */
    private $field;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="country_iso3", type="string", length=45)
     */
    private $countryIso3;

    /**
     * @var CountrySpecificAnswer
     *
     * @ORM\OneToMany(targetEntity="BeneficiaryBundle\Entity\CountrySpecificAnswer", mappedBy="countrySpecific")
     */
    private $countrySpecificAnswers;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countrySpecificAnswers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set field.
     *
     * @param string $field
     *
     * @return CountrySpecific
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return CountrySpecific
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
     * Set countryIso3.
     *
     * @param string $countryIso3
     *
     * @return CountrySpecific
     */
    public function setCountryIso3($countryIso3)
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }

    /**
     * Get countryIso3.
     *
     * @return string
     */
    public function getCountryIso3()
    {
        return $this->countryIso3;
    }

    /**
     * Add countrySpecificAnswer.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return CountrySpecific
     */
    public function addCountrySpecificAnswer(\BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param \BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(\BeneficiaryBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * Get countrySpecificAnswers.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCountrySpecificAnswers()
    {
        return $this->countrySpecificAnswers;
    }
}
