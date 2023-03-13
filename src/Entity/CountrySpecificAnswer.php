<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;
use Repository\CountrySpecificAnswerRepository;

#[ORM\Table(name: 'country_specific_answer')]
#[ORM\UniqueConstraint(name: 'only_one_household_answer', columns: ['country_specific_id', 'household_id'])]
#[ORM\Entity(repositoryClass: CountrySpecificAnswerRepository::class)]
class CountrySpecificAnswer
{
    use StandardizedPrimaryKey;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $answer;

    #[ORM\ManyToOne(targetEntity: CountrySpecific::class, inversedBy: 'countrySpecificAnswers')]
    private CountrySpecific $countrySpecific;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'countrySpecificAnswers')]
    private Household $household;

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setCountrySpecific(CountrySpecific $countrySpecific): self
    {
        $this->countrySpecific = $countrySpecific;

        return $this;
    }

    public function getCountrySpecific(): CountrySpecific
    {
        return $this->countrySpecific;
    }

    public function setHousehold(Household $household): self
    {
        $this->household = $household;

        return $this;
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }
}
