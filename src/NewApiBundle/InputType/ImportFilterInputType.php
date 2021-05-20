<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"ImportFilterInputType", "Strict"})
 */
class ImportFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"NewApiBundle\Enum\ImportState", "values"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $status;

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

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    /**
     * @return string
     */
    public function getFulltext()
    {
        return $this->fulltext;
    }

    public function hasStatus(): bool
    {
        return $this->has('status');
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }


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
}