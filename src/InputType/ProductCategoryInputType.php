<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductCategoryInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[Assert\Type('string')]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [\Enum\ProductCategoryType::class, 'values'])]
    private ?string $type = null;

    #[Assert\Type('string')]
    private ?string $image = null;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
}
