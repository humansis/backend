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

    #[Assert\NotNull]
    #[Assert\Type('array')]
    #[Assert\All(constraints: [
        new Assert\Type(['string', 'numeric'])])
    ]
    private $answers;

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
     * @return string[]
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param string[] $answers
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
    }
}
