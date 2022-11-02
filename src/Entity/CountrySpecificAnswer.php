<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * CountrySpecificAnswer
 *
 * @ORM\Table(
 *     name="country_specific_answer",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(name="only_one_household_answer", columns={"country_specific_id", "household_id"})
 *    }
 * )
 * @ORM\Entity(repositoryClass="Repository\CountrySpecificAnswerRepository")
 */
class CountrySpecificAnswer
{
    use StandardizedPrimaryKey;

    /**
     * @ORM\Column(name="answer", type="string", length=255, nullable=true)
     */
    private string $answer;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\CountrySpecific", inversedBy="countrySpecificAnswers")
     */
    private ?\Entity\CountrySpecific $countrySpecific = null;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Household", inversedBy="countrySpecificAnswers")
     */
    private ?\Entity\Household $household = null;

    /**
     * Set answer.
     *
     * @param string $answer
     *
     * @return CountrySpecificAnswer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer.
     *
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set countrySpecific.
     *
     * @param CountrySpecific|null $countrySpecific
     *
     * @return CountrySpecificAnswer
     */
    public function setCountrySpecific(CountrySpecific $countrySpecific = null)
    {
        $this->countrySpecific = $countrySpecific;

        return $this;
    }

    /**
     * Get countrySpecific.
     *
     * @return CountrySpecific|null
     */
    public function getCountrySpecific()
    {
        return $this->countrySpecific;
    }

    /**
     * Set household.
     *
     * @param Household|null $household
     *
     * @return CountrySpecificAnswer
     */
    public function setHousehold(Household $household = null)
    {
        $this->household = $household;

        return $this;
    }

    /**
     * Get household.
     *
     * @return Household|null
     */
    public function getHousehold()
    {
        return $this->household;
    }
}
