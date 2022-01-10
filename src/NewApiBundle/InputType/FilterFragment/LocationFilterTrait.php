<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;
use Happyr\Validator\Constraint\EntityExist;

trait LocationFilterTrait
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"}),
     *         @EntityExist(entity="\CommonBundle\Entity\Location", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $locations;

    /**
     * @return bool
     */
    public function hasLocations(): bool
    {
        return $this->has('locations');
    }

    /**
     * @return int[]
     */
    public function getLocations()
    {
        return $this->locations;
    }
}
