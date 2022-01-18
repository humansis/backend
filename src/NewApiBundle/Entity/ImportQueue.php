<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\EnumTrait;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Enum\ImportDuplicityState;
use NewApiBundle\Enum\ImportQueueState;
use NewApiBundle\Utils\Concurrency\ConcurrencyLockableInterface;
use NewApiBundle\Utils\Concurrency\ConcurrencyLockTrait;

/**
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ImportQueueRepository")
 */
class ImportQueue implements ConcurrencyLockableInterface
{
    use StandardizedPrimaryKey;
    use EnumTrait;
    use ConcurrencyLockTrait;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import", inversedBy="importQueue")
     */
    private $import;

    /**
     * @var ImportBeneficiaryDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportBeneficiaryDuplicity", mappedBy="ours")
     */
    private $duplicities;

    /**
     * @var ImportFile
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ImportFile", inversedBy="importQueues")
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

    private $rawMessageData = [];

    /**
     * @var ImportBeneficiaryDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportBeneficiaryDuplicity", mappedBy="ours", cascade={"remove"})
     */
    private $importBeneficiaryDuplicities;

    /**
     * @var ImportQueueDuplicity
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportQueueDuplicity", mappedBy="ours", cascade={"remove"})
     */
    private $importQueueDuplicitiesOurs;

    /**
     * @var ImportQueueDuplicity
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\ImportQueueDuplicity", mappedBy="theirs", cascade={"remove"})
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

    public function __construct(Import $import, ImportFile $file, array $content)
    {
        $this->import = $import;
        $this->file = $file;
        $this->content = $content;
        $this->state = ImportQueueState::NEW;
        $this->duplicities = new ArrayCollection();
        $this->importBeneficiaryDuplicities = new ArrayCollection();
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
     * @return ImportBeneficiaryDuplicity[]
     */
    public function getDuplicities(): Collection
    {
        return $this->duplicities;
    }

    /**
     * @return ImportBeneficiaryDuplicity|null
     */
    public function getAcceptedDuplicity(): ?ImportBeneficiaryDuplicity
    {
        foreach ($this->getDuplicities() as $duplicityCandidate) {
            if (ImportDuplicityState::DUPLICITY_KEEP_THEIRS === $duplicityCandidate->getState()
            || ImportDuplicityState::DUPLICITY_KEEP_OURS === $duplicityCandidate->getState()) {
                return $duplicityCandidate;
            }
        }
        return null;
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
     * @return string one of ImportQueueState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @see ImportQueueState::values()
     * @param string $state one of ImportQueueState::* values
     */
    public function setState(string $state)
    {
        self::validateValue('state', ImportQueueState::class, $state, false);
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function hasViolations(?int $index = null): bool
    {
        if ($index) return !empty($this->rawMessageData[$index]);
        return !empty($this->rawMessageData);
    }

    /**
     * @param $message
     */
    public function addViolation(int $lineIndex, $message): void
    {
        $this->rawMessageData[$lineIndex][] = $message;

        $this->message = json_encode($this->rawMessageData);
    }

    public function __toString()
    {
        return "ImportQueue#{$this->getId()}";
    }

    /**
     * @return ImportQueueDuplicity
     */
    public function getImportQueueDuplicitiesOurs(): ImportQueueDuplicity
    {
        return $this->importQueueDuplicitiesOurs;
    }

    /**
     * @return ImportQueueDuplicity
     */
    public function getImportQueueDuplicitiesTheirs()
    {
        return $this->importQueueDuplicitiesTheirs;
    }

    /**
     * @return Collection|ImportBeneficiaryDuplicity[]
     */
    public function getImportBeneficiaryDuplicities()
    {
        return $this->importBeneficiaryDuplicities;
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
            if ($duplicity->getState() == ImportDuplicityState::DUPLICITY_CANDIDATE) return false;
        }
        return true;
    }

}
