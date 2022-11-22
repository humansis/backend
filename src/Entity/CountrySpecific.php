<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Entity\Helper\CountryDependent;
use Entity\Helper\StandardizedPrimaryKey;
use Utils\ExportableInterface;
use Model\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * CountrySpecific
 *
 * @ORM\Table(name="country_specific", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="duplicity_check_idx", columns={"field_string", "iso3"})
 * })
 * @ORM\Entity(repositoryClass="Repository\CountrySpecificRepository")
 */
class CountrySpecific extends Criteria implements ExportableInterface
{
    use CountryDependent;
    use StandardizedPrimaryKey;

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
     * @var CountrySpecificAnswer
     *
     * @ORM\OneToMany(targetEntity="Entity\CountrySpecificAnswer", mappedBy="countrySpecific", cascade={"remove"})
     */
    private $countrySpecificAnswers;

    /**
     * CountrySpecific constructor.
     *
     * @param string $field
     * @param string $type
     * @param string $countryIso3
     */
    public function __construct(string $field, string $type, string $countryIso3)
    {
        $this->setFieldString($field)
            ->setType($type)
            ->setCountryIso3($countryIso3);
        $this->countrySpecificAnswers = new ArrayCollection();
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return CountrySpecific
     */
    public function setType(string $type): CountrySpecific
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Add countrySpecificAnswer.
     *
     * @param CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return CountrySpecific
     */
    public function addCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer): CountrySpecific
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
     * @param CountrySpecificAnswer $countrySpecificAnswer
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer): bool
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * Get countrySpecificAnswers.
     *
     * @return Collection<CountrySpecificAnswer>
     */
    public function getCountrySpecificAnswers(): Collection
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
    public function setFieldString(string $fieldString): CountrySpecific
    {
        $this->fieldString = $fieldString;

        return $this;
    }

    /**
     * Get fieldString.
     *
     * @return string
     */
    public function getFieldString(): string
    {
        return $this->fieldString;
    }

    public function getMappedValueForExport(): array
    {
        return [
            "type" => $this->getType(),
            "Country Iso3" => $this->getCountryIso3(),
            "Field" => $this->getFieldString(),
        ];
    }
}
