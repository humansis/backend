<?php

namespace DistributionBundle\Entity;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AssistanceBeneficiaryCommodity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Symfony\Component\Serializer\Annotation\MaxDepth as SymfonyMaxDepth;

use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\SmartcardDeposit;

/**
 * AssistanceBeneficiary.
 *
 * @ORM\Table(name="distribution_beneficiary")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\AssistanceBeneficiaryRepository")
 */
class AssistanceBeneficiary
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullAssistanceBeneficiary", "FullAssistance", "SmallAssistance", "ValidatedAssistance", "FullBooklet"})
     */
    private $id;

    /**
     * @var Assistance
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\Assistance", inversedBy="distributionBeneficiaries")
     * @ORM\JoinColumn(name="assistance_id")
     * @SymfonyGroups({"FullAssistanceBeneficiary", "FullBooklet"})
     */
    private $assistance;

    /**
     * @var AbstractBeneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\AbstractBeneficiary", inversedBy="assistanceBeneficiary")
     * @ORM\JoinColumn(name="beneficiary_id")
     * @SymfonyGroups({"FullAssistanceBeneficiary", "FullAssistance", "SmallAssistance", "ValidatedAssistance", "FullBooklet", "FullProject"})
     * @SymfonyMaxDepth(3)
     */
    private $beneficiary;

    /**
     * @var Collection|Transaction[]
     *
     * @ORM\OneToMany(targetEntity="TransactionBundle\Entity\Transaction", mappedBy="assistanceBeneficiary", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     * @SymfonyMaxDepth(1)
     */
    private $transactions;

    /**
     * @var Collection|Booklet[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Booklet", mappedBy="distribution_beneficiary", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     */
    private $booklets;

    /**
     * @var GeneralReliefItem
     *
     * @ORM\OneToMany(targetEntity="DistributionBundle\Entity\GeneralReliefItem", mappedBy="assistanceBeneficiary", cascade={"persist", "remove"})
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     */
    private $generalReliefs;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="json", nullable=true)
     */
    private $vulnerabilityScores;

    /**
     * @var Collection|AssistanceBeneficiaryCommodity[]
     *
     * @ORM\OneToMany(targetEntity="NewApiBundle\Entity\AssistanceBeneficiaryCommodity", mappedBy="assistanceBeneficiary")
     */
    private $assistanceBeneficiaryCommodities;

    public function __construct()
    {
        $this->booklets = new ArrayCollection();
        $this->generalReliefs = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->assistanceBeneficiaryCommodities = new ArrayCollection();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="justification", type="string", length=511, nullable=true)
     *
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     */
    private $justification;

    /**
     * @var bool
     *
     * @ORM\Column(name="removed", type="boolean", options={"default" : 0})
     *
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     */
    private $removed = 0;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     * @return bool|null true, if smartcard money was already distributed/deposited to beneficiary. Null, if distribution is not about smartcard.
     */
    public function getSmartcardDistributed(): ?bool
    {
        foreach ($this->getAssistance()->getCommodities() as $commodity) {
            /** @var Commodity $commodity */
            if ('Smartcard' === $commodity->getModalityType()->getName()) {
                return count($this->getSmartcardDeposits()) > 0;
            }
        }

        return null;
    }

    /**
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullAssistance", "SmallAssistance", "ValidatedAssistance"})
     *
     * @return \DateTimeInterface|null
     */
    public function getSmartcardDistributedAt(): ?\DateTimeInterface
    {
        foreach ($this->getSmartcardDeposits() as $deposit) {
            return $deposit->getCreatedAt();
        }

        return null;
    }

    /**
     * Set assistance.
     *
     * @param Assistance|null $assistance
     *
     * @return AssistanceBeneficiary
     */
    public function setAssistance(Assistance $assistance = null)
    {
        $this->assistance = $assistance;

        return $this;
    }

    /**
     * Get assistance.
     *
     * @return Assistance|null
     */
    public function getAssistance()
    {
        return $this->assistance;
    }

    /**
     * Set beneficiary.
     *
     * @param AbstractBeneficiary|null $beneficiary
     *
     * @return AssistanceBeneficiary
     */
    public function setBeneficiary(AbstractBeneficiary $beneficiary = null)
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return AbstractBeneficiary|null
     */
    public function getBeneficiary()
    {
        return $this->beneficiary;
    }

    /**
     * @return Collection|SmartcardDeposit[]
     */
    public function getSmartcardDeposits(): iterable
    {
        $collection = new ArrayCollection();
        foreach ($this->assistanceBeneficiaryCommodities as $commodity) {
            foreach ($commodity->getSmartcardDeposits() as $deposit) {
                $collection->add($deposit);
            }
        }
        return $collection;
    }

    /**
     * Get the value of Transaction.
     *
     * @return Collection|Transaction[]
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * Add a Transaction.
     *
     * @param Transaction $transaction
     *
     * @return self
     */
    public function addTransaction(Transaction $transaction)
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    /**
     * Remove a Transaction.
     *
     * @param Transaction $transaction
     *
     * @return self
     */
    public function removeTransaction(Transaction $transaction)
    {
        $this->transactions->removeElement($transaction);

        return $this;
    }

    /**
     * Set transactions.
     *
     * @param $collection
     *
     * @return self
     */
    public function setPhones(Collection $collection = null)
    {
        $this->transactions = $collection;

        return $this;
    }

    /**
     * @return Collection|Booklet[]
     */
    public function getBooklets(): Collection
    {
        return $this->booklets;
    }

    public function addBooklet(Booklet $booklet): self
    {
        if (!$this->booklets->contains($booklet)) {
            $this->booklets[] = $booklet;
            $booklet->setAssistanceBeneficiary($this);
        }

        return $this;
    }

    public function removeBooklet(Booklet $booklet): self
    {
        if ($this->booklets->contains($booklet)) {
            $this->booklets->removeElement($booklet);
            // set the owning side to null (unless already changed)
            if ($booklet->getAssistanceBeneficiary() === $this) {
                $booklet->setAssistanceBeneficiary(null);
            }
        }

        return $this;
    }

    /**
     * Get the value of Transaction.
     *
     * @return GeneralReliefItem
     */
    public function getGeneralReliefs()
    {
        return $this->generalReliefs;
    }

    /**
     * Add a GeneralReliefItem.
     *
     * @param GeneralReliefItem $generalRelief
     *
     * @return self
     */
    public function addGeneralRelief(GeneralReliefItem $generalRelief)
    {
        $this->generalReliefs[] = $generalRelief;

        return $this;
    }

    /**
     * Remove a GeneralReliefItem.
     *
     * @param GeneralReliefItem $generalRelief
     *
     * @return self
     */
    public function removeGeneralRelief(GeneralReliefItem $generalRelief)
    {
        $this->generalReliefs->removeElement($generalRelief);

        return $this;
    }

    /**
     * Set justification.
     *
     * @param string $justification
     *
     * @return self
     */
    public function setJustification($justification)
    {
        $this->justification = $justification;

        return $this;
    }

    /**
     * Get justification.
     *
     * @return string
     */
    public function getJustification()
    {
        return $this->justification;
    }

    /**
     * Set removed.
     *
     * @param bool $removed
     *
     * @return self
     */
    public function setRemoved($removed)
    {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Get removed.
     *
     * @return bool
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * @return string|null valid JSON string
     */
    public function getVulnerabilityScores(): ?string
    {
        return $this->vulnerabilityScores;
    }

    /**
     * @param string $vulnerabilityScores
     */
    public function setVulnerabilityScores(string $vulnerabilityScores): self
    {
        $this->vulnerabilityScores = $vulnerabilityScores;

        return $this;
    }

    /**
     * @return bool if anything was distributed to beneficiary
     */
    public function hasDistributionStarted(): bool
    {
        foreach ($this->getBooklets() as $booklet) {
            if (Booklet::UNASSIGNED !== $booklet->getStatus()) {
                return true;
            }
        }
        foreach ($this->getGeneralReliefs() as $item) {
            if (null !== $item->getDistributedAt()) {
                return true;
            }
        }
        foreach ($this->getTransactions() as $transaction) {
            if (Transaction::SUCCESS === $transaction->getTransactionStatus()) {
                return true;
            }
        }
        foreach ($this->getSmartcardDeposits() as $deposit) {
            if ($deposit->getSmartcard()->getBeneficiary() === $this->getBeneficiary()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection|AssistanceBeneficiaryCommodity[]
     */
    public function getAssistanceBeneficiaryCommodities()
    {
        return $this->assistanceBeneficiaryCommodities;
    }
}
