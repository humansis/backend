<?php

declare(strict_types=1);

namespace InputType\Import;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CreateInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * TODO array should not be empty (after FE implementation)
     *
     * @var int[]
     */
    #[Assert\All(constraints: [new Assert\Type('integer', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    private ?array $projects = null;

    /**
     * TODO remove after FE part of PIN-2820 will be implemented
     *
     *
     */
    #[Assert\Type('integer')]
    private ?int $projectId = null;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param int[] $projects
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }
}
