<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
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
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $adms1;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $adms2;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $adms3;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $adms4;

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

    /**
     * @return bool
     */
    public function hasAdms1(): bool
    {
        return $this->has('adms1');
    }

    /**
     * @return int[]
     */
    public function getAdms1()
    {
        return $this->adms1;
    }

    /**
     * @return bool
     */
    public function hasAdms2(): bool
    {
        return $this->has('adms2');
    }

    /**
     * @return int[]
     */
    public function getAdms2()
    {
        return $this->adms2;
    }

    /**
     * @return bool
     */
    public function hasAdms3(): bool
    {
        return $this->has('adms3');
    }

    /**
     * @return int[]
     */
    public function getAdms3()
    {
        return $this->adms3;
    }

    /**
     * @return bool
     */
    public function hasAdms4(): bool
    {
        return $this->has('adms4');
    }

    /**
     * @return int[]
     */
    public function getAdms4()
    {
        return $this->adms4;
    }
}
