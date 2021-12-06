<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Component\Import\Enum\DuplicityState;
use NewApiBundle\Component\Import\Enum\QueueState;
use UserBundle\Entity\User;

/**
 * Imformation about duplicity between two queue records.
 *
 * @ORM\Table(name="import_queue_duplicity")
 * @ORM\Entity()
 */
class QueueDuplicity
{
    use StandardizedPrimaryKey;

    /**
     * @var Queue
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\Queue", inversedBy="importQueueDuplicitiesOurs")
     */
    private $ours;

    /**
     * @var Queue
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\Queue", inversedBy="importQueueDuplicitiesTheirs")
     */
    private $theirs;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="enum_import_duplicity_state", nullable=false)
     */
    private $state;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $decideBy;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="decide_at", type="datetimetz", nullable=false)
     */
    private $decideAt;

    public function __construct(Queue $ours, Queue $theirs)
    {
        $this->ours = $ours;
        $this->theirs = $theirs;
        $this->state = DuplicityState::DUPLICITY_CANDIDATE;
    }

    /**
     * @return Queue
     */
    public function getOurs(): Queue
    {
        return $this->ours;
    }

    /**
     * @return Queue
     */
    public function getTheirs(): Queue
    {
        return $this->theirs;
    }

    /**
     * @return string one of DuplicityState::* values
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state one of DuplicityState::* values
     */
    public function setState(string $state)
    {
        if (!in_array($state, DuplicityState::values())) {
            throw new \InvalidArgumentException('Invalid argument. '.$state.' is not valid Import duplicity state');
        }

        $this->state = $state;
    }

    /**
     * @param User $decideBy
     */
    public function setDecideBy(User $decideBy): void
    {
        $this->decideBy = $decideBy;
    }

    /**
     * @return User
     */
    public function getDecideBy(): User
    {
        return $this->decideBy;
    }

    /**
     * @param \DateTimeInterface $dateTime
     */
    public function setDecideAt(\DateTimeInterface $dateTime): void
    {
        $this->decideAt = $dateTime;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDecideAt(): \DateTimeInterface
    {
        return $this->decideAt;
    }
}
