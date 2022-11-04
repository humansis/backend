<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Entity\Helper\CountryDependent;
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

    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id;

    /**
     * @ORM\Column(name="field_string", type="string", length=45)
     */
    private ?string $fieldString = null;

    /**
     * @ORM\Column(name="type", type="string", length=45)
     */
    private ?string $type = null;

    /**
     * @ORM\OneToMany(targetEntity="Entity\CountrySpecificAnswer", mappedBy="countrySpecific", cascade={"remove"})
     */
    private \Doctrine\Common\Collections\Collection|array $countrySpecificAnswers;

    /**
     * CountrySpecific constructor.
     */
    public function __construct(string $field, string $type, string $countryIso3)
    {
        $this->setFieldString($field)
            ->setType($type)
            ->setCountryIso3($countryIso3);
        $this->countrySpecificAnswers = new ArrayCollection();
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set type.
     *
     *
     */
    public function setType(string $type): CountrySpecific
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Add countrySpecificAnswer.
     *
     *
     */
    public function addCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer): CountrySpecific
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * Remove countrySpecificAnswer.
     *
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
     *
     */
    public function setFieldString(string $fieldString): CountrySpecific
    {
        $this->fieldString = $fieldString;

        return $this;
    }

    /**
     * Get fieldString.
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
