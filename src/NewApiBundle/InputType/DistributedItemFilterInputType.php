<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"DistributedItemFilterInputType", "Strict"})
 */
class DistributedItemFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $projects;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $locations;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $assistances;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $modalityTypes;

    /**
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    public function hasProjects(): bool
    {
        return $this->has('projects');
    }

    /**
     * @return int[]
     */
    public function getProjects(): array
    {
        return $this->projects;
    }

    public function hasModalityTypes(): bool
    {
        return $this->has('modalityTypes');
    }

    /**
     * @return string[]
     */
    public function getModalityTypes(): array
    {
        return $this->modalityTypes;
    }

    public function hasLocations(): bool
    {
        return $this->has('locations');
    }

    /**
     * @return int[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    public function hasAssistances(): bool
    {
        return $this->has('assistances');
    }

    /**
     * @return int[]
     */
    public function getAssistances(): array
    {
        return $this->assistances;
    }

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }
}
