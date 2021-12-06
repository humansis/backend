<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Component\Import\Enum\DuplicityState;
use NewApiBundle\Component\Import\Enum\QueueState;

/**
 * @ORM\Table(name="import_queue")
 * @ORM\Entity(repositoryClass="NewApiBundle\Component\Import\Repository\QueueRepository")
 */
class Queue
{
    use StandardizedPrimaryKey;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\Import", inversedBy="importQueue")
     */
    private $import;

    /**
     * @var BeneficiaryDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\BeneficiaryDuplicity", mappedBy="ours")
     */
    private $duplicities;

    /**
     * @var File
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\File", inversedBy="importQueues")
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

    /**
     * @var QueueDuplicity
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\QueueDuplicity", mappedBy="ours", cascade={"remove"})
     */
    private $importQueueDuplicitiesOurs;

    /**
     * @var QueueDuplicity
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Component\Import\Entity\QueueDuplicity", mappedBy="theirs", cascade={"remove"})
     */
    private $importQueueDuplicitiesTheirs;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="identity_checked_at", type="datetimetz", nullable=true)
     */
    private $identityCheckedAt;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="similarity_checked_at", type="datetimetz", nullable=true)
     */
    private $similarityCheckedAt;

    public function __construct(Import $import, File $file, array $content)
    {
        $this->import = $import;
        $this->file = $file;
        $this->content = $content;
        $this->state = QueueState::NEW;
        $this->duplicities = new ArrayCollection();
        $this->importQueueDuplicitiesOurs = new ArrayCollection();
        $this->importQueueDuplicitiesTheirs = new ArrayCollection();
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return BeneficiaryDuplicity[]
     */
    public function getDuplicities(): Collection
    {
        return $this->duplicities;
    }

    /**
     * @return BeneficiaryDuplicity|null
     */
    public function getAcceptedDuplicity(): ?BeneficiaryDuplicity
    {
        foreach ($this->getDuplicities() as $duplicityCandidate) {
            if (DuplicityState::DUPLICITY_KEEP_THEIRS === $duplicityCandidate->getState()
            || DuplicityState::DUPLICITY_KEEP_OURS === $duplicityCandidate->getState()) {
                return $duplicityCandidate;
            }
        }
        return null;
    }

    /**
     * @return File
     */
    public function getFile(): File
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
     * @return array json object representation
     */
    public function getHeadContent(): array
    {
        return $this->content[0];
    }

    /**
     * @return array json object representation
     */
    public function getMemberContents(): array
    {
        return array_slice($this->content, 1);
    }

    /**
     * @return string one of QueueState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of QueueState::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, QueueState::values())) {
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

    public function __toString()
    {
        return "Queue#{$this->getId()}";
    }

    /**
     * @return QueueDuplicity
     */
    public function getQueueDuplicitiesOurs(): QueueDuplicity
    {
        return $this->importQueueDuplicitiesOurs;
    }

    /**
     * @return QueueDuplicity
     */
    public function getQueueDuplicitiesTheirs()
    {
        return $this->importQueueDuplicitiesTheirs;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getIdentityCheckedAt(): ?\DateTimeInterface
    {
        return $this->identityCheckedAt;
    }

    /**
     * @param \DateTimeInterface|null $identityCheckedAt
     */
    public function setIdentityCheckedAt(?\DateTimeInterface $identityCheckedAt): void
    {
        $this->identityCheckedAt = $identityCheckedAt;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getSimilarityCheckedAt(): ?\DateTimeInterface
    {
        return $this->similarityCheckedAt;
    }

    /**
     * @param \DateTimeInterface|null $similarityCheckedAt
     */
    public function setSimilarityCheckedAt(?\DateTimeInterface $similarityCheckedAt): void
    {
        $this->similarityCheckedAt = $similarityCheckedAt;
    }

    public function hasResolvedDuplicities(): bool
    {
        foreach ($this->getDuplicities() as $duplicity) {
            if ($duplicity->getState() == DuplicityState::DUPLICITY_CANDIDATE) return false;
        }
        return true;
    }

}
