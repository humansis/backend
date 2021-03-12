<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class ContactInputType extends PersonInputType
{
    /**
     * @Assert\NotNull
     */
    protected $enGivenName;

    /**
     * @Assert\NotNull
     */
    protected $enFamilyName;

    /**
     * @Assert\NotBlank
     */
    protected $nationalIdCards = [];

    /**
     * @Assert\NotBlank
     */
    protected $phones = [];
}
