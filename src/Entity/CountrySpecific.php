<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Entity\Helper\CountryDependent;
use Entity\Helper\StandardizedPrimaryKey;
use Repository\CountrySpecificRepository;
use Utils\ExportableInterface;
use Model\Criteria;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'country_specific')]
#[ORM\UniqueConstraint(name: 'duplicity_check_idx', columns: ['field_string', 'iso3'])]
#[ORM\Entity(repositoryClass: CountrySpecificRepository::class)]
class CountrySpecific extends Criteria implements ExportableInterface
{
    use CountryDependent;
    use StandardizedPrimaryKey;

    #[ORM\Column(type: 'string', length: 45)]
    private string $fieldString;

    #[ORM\Column(type: 'string', length: 45)]
    private string $type;

    #[ORM\OneToMany(mappedBy: 'countrySpecific', targetEntity: CountrySpecificAnswer::class, cascade: ['remove'])]
    private Collection $countrySpecificAnswers;

    public function __construct(string $field, string $type, string $countryIso3)
    {
        $this->setFieldString($field)
            ->setType($type)
            ->setCountryIso3($countryIso3);

        $this->countrySpecificAnswers = new ArrayCollection();
    }

    public function setType(string $type): CountrySpecific
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer): CountrySpecific
    {
        $this->countrySpecificAnswers[] = $countrySpecificAnswer;

        return $this;
    }

    /**
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer): bool
    {
        return $this->countrySpecificAnswers->removeElement($countrySpecificAnswer);
    }

    /**
     * @return Collection<CountrySpecificAnswer>
     */
    public function getCountrySpecificAnswers(): Collection
    {
        return $this->countrySpecificAnswers;
    }

    public function setFieldString(string $fieldString): CountrySpecific
    {
        $this->fieldString = $fieldString;

        return $this;
    }

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
