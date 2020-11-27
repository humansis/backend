<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class ProductCreateInputType extends ProductUpdateInputType
{
    /**
     * @var string
     * @Assert\LessThanOrEqual(255)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $name;

    /**
     * @var string
     * @Assert\Choice({"KHM", "SYR", "UKR", "ETH"})
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $iso3;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getIso3(): string
    {
        return $this->iso3;
    }

    /**
     * @param string $iso3
     */
    public function setIso3(string $iso3): void
    {
        $this->iso3 = $iso3;
    }
}
