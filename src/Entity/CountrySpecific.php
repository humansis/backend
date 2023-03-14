<?php

declare(strict_types=1);

namespace Entity;

use Component\CSO\Enum\CountrySpecificType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CountryDependent;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;
use Model\Criteria;
use Repository\CountrySpecificRepository;
use Utils\ExportableInterface;

#[ORM\Table(name: 'country_specific')]
#[ORM\UniqueConstraint(name: 'duplicity_check_idx', columns: ['field_string', 'iso3'])]
#[ORM\Entity(repositoryClass: CountrySpecificRepository::class)]
class CountrySpecific extends Criteria implements ExportableInterface
{
    use CountryDependent;
    use StandardizedPrimaryKey;

    use EnumTrait;

    #[ORM\Column(type: 'string', length: 45)]
    private string $fieldString;

    #[ORM\Column(type: 'enum_country_specific_type')]
    private string $type;

    #[ORM\OneToMany(mappedBy: 'countrySpecific', targetEntity: CountrySpecificAnswer::class, cascade: ['remove'])]
    private Collection $countrySpecificAnswers;

    /**
     * If true, a household can have more than one answer for this CSO.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $multiValue;

    public function __construct(string $field, string $type, string $countryIso3, bool $multiValue = false)
    {
        $this->fieldString = $field;
        $this->setType($type);
        $this->countryIso3 = $countryIso3;
        $this->multiValue = $multiValue;

        $this->countrySpecificAnswers = new ArrayCollection();
    }

    public function setType(string $type): void
    {
        self::validateValue('type', CountrySpecificType::class, $type);

        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addCountrySpecificAnswer(CountrySpecificAnswer $countrySpecificAnswer): void
    {
        $this->countrySpecificAnswers->add($countrySpecificAnswer);
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

    public function setFieldString(string $fieldString): void
    {
        $this->fieldString = $fieldString;
    }

    public function getFieldString(): string
    {
        return $this->fieldString;
    }

    public function isMultiValue(): bool
    {
        return $this->multiValue;
    }

    public function setMultiValue(bool $multiValue): void
    {
        $this->multiValue = $multiValue;
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
