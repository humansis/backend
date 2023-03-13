<?php

declare(strict_types=1);

namespace InputType\Beneficiary;

use Happyr\Validator\Constraint\EntityExist;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CountrySpecificsAnswerInputType implements InputTypeInterface
{
    /**
     * @EntityExist(entity="Entity\CountrySpecific")
     */
    #[Assert\NotNull]
    private $countrySpecificId;

    #[Assert\Type(type: ['string', 'numeric'], message: "Value '{{ value }}' should be of type {{ type }}")]
    #[Assert\Length(max: 255)]
    private $answer;

    /**
     * @return int
     */
    public function getCountrySpecificId()
    {
        return $this->countrySpecificId;
    }

    /**
     * @param int $countrySpecificId
     */
    public function setCountrySpecificId($countrySpecificId)
    {
        $this->countrySpecificId = $countrySpecificId;
    }

    /**
     * @return string|null
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param string|null $answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }
}
