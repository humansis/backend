<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;

class ProductUpdateInputType implements \CommonBundle\InputType\InputTypeInterface
{
    /**
     * @var string|null
     * @Assert\LessThanOrEqual(20)
     */
    private $unit;

    /**
     * @var string|null
     * @Assert\LessThanOrEqual(255)
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $image;

    /**
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @param string|null $unit
     */
    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     */
    public function setImage(?string $image): void
    {
        $this->image = $image;
    }
}
