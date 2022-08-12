<?php

namespace NewApiBundle\Entity;

use NewApiBundle\Utils\ExportableInterface;
use NewApiBundle\Model\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * CountrySpecific
 *
 * @ORM\Table(name="country_specific", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="duplicity_check_idx", columns={"field_string", "country_iso3"})
 * })
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\CountrySpecificRepository")
 */
class CountrySpecific extends Criteria implements ExportableInterface
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
     * @ORM\Column(name="field_string", type="string", length=45)
     */
    private $fieldString;

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
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\CountrySpecificAnswer", mappedBy="countrySpecific", cascade={"remove"})
     */
    private $countrySpecificAnswers;

    /**
     * CountrySpecific constructor.
     * @param $field
     * @param $type
     * @param $countryIso3
     */
    public function __construct($field, $type, $countryIso3)
    {
        $this->setFieldString($field)
            ->setType($type)
            ->setCountryIso3($countryIso3);
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
     * @param \NewApiBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return CountrySpecific
     */
    public function addCountrySpecificAnswer(\NewApiBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param \NewApiBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(\NewApiBundle\Entity\CountrySpecificAnswer $countrySpecificAnswer)
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

    /**
     * Set fieldString.
     *
     * @param string $fieldString
     *
     * @return CountrySpecific
     */
    public function setFieldString($fieldString)
    {
        $this->fieldString = $fieldString;

        return $this;
    }

    /**
     * Get fieldString.
     *
     * @return string
     */
    public function getFieldString()
    {
        return $this->fieldString;
    }


    public function getMappedValueForExport(): array
    {
        return [
            "type" => $this->getType(),
            "Country Iso3"=> $this->getCountryIso3(),
            "Field" => $this->getFieldString()
        ];
    }
}
