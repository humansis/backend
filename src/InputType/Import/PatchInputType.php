<?php

declare(strict_types=1);

namespace InputType\Import;

use Enum\ImportState;
use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PatchInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    private ?string $description = null;

    #[Assert\Type('string')]
    #[Assert\NotBlank(allowNull: true)]
    private ?string $title = null;

    /**
     * ImportUpdateStatusInputType constructor.
     */
    public function __construct(private ?string $status = null)
    {
    }

    public static function allowedStates(): array
    {
        return [
            ImportState::IDENTITY_CHECKING,
            ImportState::SIMILARITY_CHECKING,
            ImportState::IMPORTING,
            ImportState::CANCELED,
        ];
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }
}
