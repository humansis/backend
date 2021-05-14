<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Enum\ImportQueueState;

/**
 * @ORM\Entity()
 */
class ImportQueue
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import")
     */
    private $import;

    /**
     * @var ImportFile
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ImportFile")
     */
    private $file;

    /**
     * @var array
     *
     * @ORM\Column(name="content", type="json", nullable=false)
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_import_queue_state", nullable=false)
     */
    private $state;

    /**
     * @var string|null
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    public function __construct(Import $import, ImportFile $file, $content)
    {
        $this->import = $import;
        $this->file = $file;
        $this->content = $content;
        $this->state = ImportQueueState::NEW;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return ImportFile
     */
    public function getFile(): ImportFile
    {
        return $this->file;
    }

    /**
     * @return array json object representation
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return string one of ImportQueueState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of ImportQueueState::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, ImportQueueState::values())) {
            throw new \InvalidArgumentException('Invalid argument. '.$state.' is not valid Import queue state');
        }

        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }
}
