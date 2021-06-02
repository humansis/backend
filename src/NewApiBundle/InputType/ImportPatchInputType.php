<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Enum\ImportState;
use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ImportPatchInputType implements InputTypeInterface
{
    /**
     * @var string|null
     *
     * @Assert\Type("string")
     * @Assert\Choice(callback="allowedStates")
     */
    private $status;

    /**
     * @var string|null
     *
     * @Assert\Type("string")
     */
    private $description;

    /**
     * ImportUpdateStatusInputType constructor.
     *
     * @param string|null $status
     */
    public function __construct(?string $status = null)
    {
        $this->status = $status;
    }

    public static function allowedStates(): array
    {
        return [
            ImportState::INTEGRITY_CHECKING,
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
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
