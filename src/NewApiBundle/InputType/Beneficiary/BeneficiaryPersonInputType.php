<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Beneficiary;

use NewApiBundle\InputType\PersonInputType;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryPersonInputType extends PersonInputType
{
    /**
     * @Assert\NotBlank
     */
    protected $dateOfBirth;

    /**
     * @Assert\NotBlank
     */
    protected $localFamilyName;

    /**
     * @Assert\NotBlank
     */
    protected $localGivenName;

    /**
     * @Assert\NotBlank
     */
    protected $gender;
}
