<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Validator\Constraints\IsBase64;
use Symfony\Component\Validator\Constraints as Assert;

class ScoringInputType implements InputTypeInterface
{


    /**
     * @var string
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $name;

    /**
     * @Assert\Type("bool")
     */
    private $archived = false;

    /**
     * @IsBase64()
     * @Assert\Type("string")
     * @Assert\NotBlank
     */
    private $content;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ScoringInputType
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived;
    }

    /**
     * @param bool $archived
     *
     * @return ScoringInputType
     */
    public function setArchived(bool $archived): ScoringInputType
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return base64_decode($this->content);
    }

    /**
     * @param mixed $content
     *
     * @return ScoringInputType
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }



}
