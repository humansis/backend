<?php

declare(strict_types=1);

namespace Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Component\Import\Finishing\UnexpectedError;
use Component\Import\Integrity\QueueViolation;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\ImportDuplicityState;
use Enum\ImportQueueState;
use InvalidArgumentException;
use Stringable;
use Utils\Concurrency\ConcurrencyLockableInterface;
use Utils\Concurrency\ConcurrencyLockTrait;

/**
 * @ORM\Entity(repositoryClass="Repository\ImportQueueRepository")
 */
class ImportQueue implements ConcurrencyLockableInterface, Stringable
{
    use StandardizedPrimaryKey;
    use EnumTrait;
    use ConcurrencyLockTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\Import", inversedBy="importQueue")
     */
    private Import $import;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\ImportFile", inversedBy="importQueues")
     */
    private ImportFile $file;

    /**
     * @ORM\Column(name="content", type="json", nullable=false)
     */
    private array $content;

    /**
     * @var ImportHouseholdDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="ImportHouseholdDuplicity", mappedBy="ours", cascade={"persist", "remove"})
     */
    private array | Collection $householdDuplicities;

    /**
     * @var ImportBeneficiaryDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Entity\ImportBeneficiaryDuplicity", mappedBy="queue", cascade={"persist", "remove"})
     */
    private array | Collection $beneficiaryDuplicities;

    /**
     * @ORM\Column(name="state", type="enum_import_queue_state", nullable=false)
     */
    private string $state;

    /**
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private ?string $message = null;

    private array $rawMessageData = [];

    /**
     * @var ImportHouseholdDuplicity[]|Collection
     *
     * @ORM\OneToMany(targetEntity="ImportHouseholdDuplicity", mappedBy="ours", cascade={"remove"})
     */
    private array | Collection $importBeneficiaryDuplicities;

    /**
     * @var Collection|ImportQueueDuplicity[]
     * @ORM\OneToMany(targetEntity="Entity\ImportQueueDuplicity", mappedBy="ours", cascade={"remove"})
     */
    private array | Collection $importQueueDuplicitiesOurs;

    /**
     * @var Collection|ImportQueueDuplicity[]
     * @ORM\OneToMany(targetEntity="Entity\ImportQueueDuplicity", mappedBy="theirs", cascade={"remove"})
     */
    private array | Collection $importQueueDuplicitiesTheirs;

    /**
     * @ORM\Column(name="identity_checked_at", type="datetimetz", nullable=true)
     */
    private ?DateTimeInterface $identityCheckedAt = null;

    /**
     * @ORM\Column(name="similarity_checked_at", type="datetimetz", nullable=true)
     */
    private ?DateTimeInterface $similarityCheckedAt = null;

    private array $violatedColumns = [];

    public function __construct(Import $import, ImportFile $file, array $content)
    {
        $this->import = $import;
        $this->file = $file;
        $this->content = $content;
        $this->state = ImportQueueState::NEW;
        $this->householdDuplicities = new ArrayCollection();
        $this->beneficiaryDuplicities = new ArrayCollection();
        $this->importBeneficiaryDuplicities = new ArrayCollection();
        $this->importQueueDuplicitiesOurs = [];
        $this->importQueueDuplicitiesTheirs = [];
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return ImportHouseholdDuplicity[]
     */
    public function getHouseholdDuplicities(): Collection
    {
        return $this->householdDuplicities;
    }

    public function getAcceptedDuplicity(): ?ImportHouseholdDuplicity
    {
        foreach ($this->getHouseholdDuplicities() as $duplicityCandidate) {
            if (
                ImportDuplicityState::DUPLICITY_KEEP_THEIRS === $duplicityCandidate->getState()
                || ImportDuplicityState::DUPLICITY_KEEP_OURS === $duplicityCandidate->getState()
            ) {
                return $duplicityCandidate;
            }
        }

        return null;
    }

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
     * @param string $state one of ImportQueueState::* values
     * @see ImportQueueState::values()
     */
    public function setState(string $state)
    {
        self::validateValue('state', ImportQueueState::class, $state, false);
        $this->state = $state;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function hasViolations(?int $index = null): bool
    {
        if ($index) {
            return !empty($this->rawMessageData[$index]);
        }

        return !empty($this->rawMessageData);
    }

    public function addViolation(QueueViolation $queueViolation): void
    {
        $this->rawMessageData[$queueViolation->getLineIndex()][] = [
            'column' => $queueViolation->getColumn(),
            'violation' => $queueViolation->getMessage(),
            'value' => $queueViolation->getValue(),
        ];

        $this->message = json_encode($this->rawMessageData, JSON_THROW_ON_ERROR);
        $this->violatedColumns[$queueViolation->getLineIndex()][] = $queueViolation->getColumn();
    }

    public function setUnexpectedError(UnexpectedError $error): void
    {
        $this->rawMessageData[-1] = $error->jsonSerialize();

        $this->message = json_encode($this->rawMessageData, JSON_THROW_ON_ERROR);
    }

    public function hasColumnViolation(int $index, string $column): bool
    {
        return key_exists($index, $this->violatedColumns) && in_array($column, $this->violatedColumns[$index]);
    }

    public function __toString(): string
    {
        return (string) "ImportQueue#{$this->getId()}";
    }

    public function addDuplicity(int $index, Beneficiary $beneficiary, array $reasons): void
    {
        if ($index < 0 || $index >= count($this->content)) {
            throw new InvalidArgumentException("Member index '{$index}' was not found in imported Household");
        }

        $householdDuplicity = $this->getHouseholdDuplicityById($beneficiary->getHouseholdId());
        if (!$householdDuplicity) {
            $householdDuplicity = new ImportHouseholdDuplicity($this, $beneficiary->getHousehold());
            $this->householdDuplicities->add($householdDuplicity);
        }

        $beneficiaryDuplicity = new ImportBeneficiaryDuplicity($householdDuplicity, $this, $index, $beneficiary);
        $this->beneficiaryDuplicities->add($beneficiaryDuplicity);

        foreach ($reasons as $reason) {
            $beneficiaryDuplicity->addReason($reason);
        }
    }

    public function getHouseholdDuplicityById(int $householdId): ?ImportHouseholdDuplicity
    {
        foreach ($this->householdDuplicities as $householdDuplicity) {
            if ($householdDuplicity->getTheirs()->getId() === $householdId) {
                return $householdDuplicity;
            }
        }

        return null;
    }

    /**
     * @return Collection|ImportBeneficiaryDuplicity[]
     */
    public function getBeneficiaryDuplicities(): Collection | array
    {
        return $this->beneficiaryDuplicities;
    }

    public function getImportQueueDuplicitiesOurs(): ImportQueueDuplicity
    {
        return $this->importQueueDuplicitiesOurs;
    }

    /**
     * @return Collection|ImportQueueDuplicity[]
     */
    public function getImportQueueDuplicitiesTheirs()
    {
        return $this->importQueueDuplicitiesTheirs;
    }

    /**
     * @return Collection|ImportHouseholdDuplicity[]
     */
    public function getImportBeneficiaryDuplicities(): Collection | array
    {
        return $this->importBeneficiaryDuplicities;
    }

    public function getIdentityCheckedAt(): ?DateTimeInterface
    {
        return $this->identityCheckedAt;
    }

    public function setIdentityCheckedAt(?DateTimeInterface $identityCheckedAt): void
    {
        $this->identityCheckedAt = $identityCheckedAt;
    }

    public function getSimilarityCheckedAt(): ?DateTimeInterface
    {
        return $this->similarityCheckedAt;
    }

    public function setSimilarityCheckedAt(?DateTimeInterface $similarityCheckedAt): void
    {
        $this->similarityCheckedAt = $similarityCheckedAt;
    }

    public function hasResolvedDuplicities(): bool
    {
        foreach ($this->getHouseholdDuplicities() as $duplicity) {
            if ($duplicity->getState() == ImportDuplicityState::DUPLICITY_CANDIDATE) {
                return false;
            }
        }

        return true;
    }
}
