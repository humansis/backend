<?php
namespace BeneficiaryBundle\Entity;

use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ProjectBundle\Entity\Project;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * @ORM\Entity()
 * @ORM\Table(name="abstract_beneficiary")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="bnf_type", type="string")
 * @ORM\DiscriminatorMap({
 *     "bnf" = "Beneficiary",
 *     "hh" = "Household",
 *     "inst" = "Institution",
 *     "comm" = "Community"
 * })
*/
abstract class AbstractBeneficiary
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullProject", "FullBeneficiary", "SmartcardOverview", "FullSmartcard"})
     * @SymfonyGroups({"SmartcardOverview", "FullSmartcard"})
     */
    protected $id;

    /**
     * @var Project[]|Collection
     * @ORM\ManyToMany(targetEntity="ProjectBundle\Entity\Project", inversedBy="households", cascade={"persist"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold"})
     */
    private $projects;

    /**
     * @var AssistanceBeneficiary[]|Collection
     *
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\AssistanceBeneficiary", mappedBy="beneficiary", cascade={"remove"})
     */
    private $assistanceBeneficiaries;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $archived = 0;

    /**
     * AbstractBeneficiary constructor.
     */
    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->assistanceBeneficiaries = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Add project.
     *
     * @param Project $project
     *
     * @return Household
     */
    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
        }

        return $this;
    }

    /**
     * Remove project.
     *
     * @param Project $project
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProject(Project $project): bool
    {
        return $this->projects->removeElement($project);
    }

    /**
     * Get projects.
     *
     * @return ArrayCollection|Project[]
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    /**
     * Set project.
     *
     * @param Collection|null $collection
     *
     * @return self
     */
    public function setProjects(Collection $collection = null): self
    {
        $this->projects = $collection;

        return $this;
    }

    /**
     * @return AssistanceBeneficiary[]|Collection
     */
    public function getAssistanceBeneficiaries(): Collection
    {
        return $this->assistanceBeneficiaries;
    }

    /**
     * @param AssistanceBeneficiary[]|Collection $assistanceBeneficiaries
     */
    public function setAssistanceBeneficiaries(array $assistanceBeneficiaries): void
    {
        $this->assistanceBeneficiaries = $assistanceBeneficiaries;
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return self
     */
    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }
}
