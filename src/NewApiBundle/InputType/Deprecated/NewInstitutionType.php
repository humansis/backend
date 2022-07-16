<?php

declare(strict_types=1);

namespace NewApiBundle\InputType\Deprecated;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NewInstitutionType extends UpdateInstitutionType implements InputTypeInterface
{
    /**
     * @var string
     * @Assert\Length(max="255")
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @var string
     * @Assert\Length(max="255")
     * @Assert\NotBlank()
     * @Assert\Choice(choices=NewApiBundle\Entity\Institution::TYPE_ALL)
     */
    protected $type;
}
