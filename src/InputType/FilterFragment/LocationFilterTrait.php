<?php
declare(strict_types=1);

namespace InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;

trait LocationFilterTrait
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
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
