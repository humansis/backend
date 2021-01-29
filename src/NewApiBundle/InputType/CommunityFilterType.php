<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use Symfony\Component\Validator\Constraints as Assert;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;

/**
 * Class CommunityFilterType
 * @package NewApiBundle\InputType
 */
class CommunityFilterType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Type("string")
     */
    protected $fulltext;

    /**
     * @var string[]
     * @Assert\Type("string")
     */
    protected $projectName;

    /**
     * @var string[]
     * @Assert\Type("string")
     */
    protected $name;

    /**
     * @return string
     */
    public function getFulltext(): string
    {
        return $this->fulltext;
    }

    /**
     * @return bool
     */
    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    /**
     * @return string[]
     */
    public function getProjectName(): array
    {
        return $this->projectName;
    }

    /**
     * @return bool
     */
    public function hasProjectName(): bool
    {
        return $this->has('projectName');
    }

    /**
     * @return string[]
     */
    public function getName(): array
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasName(): bool
    {
        return $this->has('name');
    }
}
