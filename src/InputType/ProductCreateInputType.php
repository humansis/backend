<?php

declare(strict_types=1);

namespace InputType;

use Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints as Assert;

class ProductCreateInputType extends ProductUpdateInputType
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $name;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Country]
    private $iso3;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;
    }
}
