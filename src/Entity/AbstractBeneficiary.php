<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

#[ORM\Table(name: 'abstract_beneficiary')]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'bnf_type', type: 'string')]
#[ORM\DiscriminatorMap(['bnf' => 'Beneficiary', 'hh' => 'Household', 'inst' => 'Institution', 'comm' => 'Community'])]
abstract class AbstractBeneficiary
{
    use StandardizedPrimaryKey;

    /**
     * @var Project[]|Collection
     */
    #[ORM\ManyToMany(targetEntity: 'Entity\Project', inversedBy: 'households', cascade: ['persist'])]
    private $projects;

    /**
     * @var AssistanceBeneficiary[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'beneficiary', targetEntity: 'Entity\AssistanceBeneficiary', cascade: ['remove'])]
    private Collection | array $distributionBeneficiaries;

    /**
     * @var AssistanceBeneficiary[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'beneficiary', targetEntity: 'Entity\AssistanceBeneficiary', cascade: ['remove'])]
    #[ORM\JoinColumn(name: 'distribution_beneficiary_id')]
    private Collection | array $assistanceBeneficiary;

    #[ORM\Column(type: 'boolean', options: ['default' => 0])]
    private int|bool $archived = 0;

    /**
     * AbstractBeneficiary constructor.
     */
    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->distributionBeneficiaries = new ArrayCollection();
    }

    /**
     * Add project.
     *
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
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
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
     * @return AssistanceBeneficiary
     */
    public function getAssistanceBeneficiary(): ?AssistanceBeneficiary
    {
        return $this->distributionBeneficiaries->getIterator()->current();
    }

    /**
     * @return AssistanceBeneficiary[]|Collection
     */
    public function getDistributionBeneficiaries(): Collection
    {
        return $this->distributionBeneficiaries;
    }

    /**
     * @param AssistanceBeneficiary[]|Collection $distributionBeneficiaries
     */
    public function setDistributionBeneficiaries(array $distributionBeneficiaries): void
    {
        $this->distributionBeneficiaries = $distributionBeneficiaries;
    }

    /**
     * Set archived.
     *
     *
     * @return self
     */
    public function setArchived(bool $archived = true): self
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
