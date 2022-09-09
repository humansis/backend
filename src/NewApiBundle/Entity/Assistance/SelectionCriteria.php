<?php declare(strict_types=1);

namespace NewApiBundle\Entity\Assistance;

use BeneficiaryBundle\Entity\CountrySpecific;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceSelection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use NewApiBundle\Enum\SelectionCriteriaField;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * SelectionCriteria - user filled criteria
 *
 * @ORM\Table(name="selection_criteria")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\SelectionCriteriaRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SelectionCriteria
{
    use StandardizedPrimaryKey;

    /**
     * @var AssistanceSelection
     *
     * @ORM\ManyToOne(targetEntity="DistributionBundle\Entity\AssistanceSelection", inversedBy="selectionCriteria")
     * @ORM\JoinColumn(name="assistance_selection_id", nullable=false)
     */
    private $assistanceSelection;

    /**
     * @var string
     *
     * @ORM\Column(name="table_string", type="string", length=255)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $tableString;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $target;

    /**
     * @var string
     *
     * @ORM\Column(name="field_string", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $fieldString;

    /**
     * @var int
     *
     * @ORM\Column(name="field_id", type="integer", nullable=true)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $idField;

    /**
     * @var string|null
     *
     * @ORM\Column(name="condition_string", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $conditionString;

    /**
     * @var string
     *
     * @ORM\Column(name="value_string", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $valueString;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="integer")
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $weight;

    /**
     * @var int
     *
     * @ORM\Column(name="group_number", type="integer", nullable=false)
     * @SymfonyGroups({"FullAssistance", "SmallAssistance"})
     */
    private $groupNumber;

    /**
     * @var bool
     *
     */
    private $deprecated = true;

    /**
     * @param LifecycleEventArgs $lifecycleEventArgs
     *
     * @return void
     * @ORM\PostLoad()
     */
    public function postLoad(LifecycleEventArgs $lifecycleEventArgs): void
    {
        if ($this->tableString === SelectionCriteriaField::COUNTRY_SPECIFIC) {
            $iso3 = $this->assistanceSelection->getAssistance()->getProject()->getCountryIso3();
            $this->deprecated = $lifecycleEventArgs->getEntityManager()
                ->getRepository(CountrySpecific::class)
                ->findOneBy(['fieldString' => $this->fieldString, 'countryIso3' => $iso3]) === null;
        } else {
            $this->deprecated = !in_array($this->fieldString, SelectionCriteriaField::values());
        }
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight)
    {
        $this->weight = $weight;
    }

    /**
     * Set tableString.
     *
     * @param string $tableString
     *
     * @return SelectionCriteria
     */
    public function setTableString(string $tableString)
    {
        $this->tableString = $tableString;

        return $this;
    }

    /**
     * Get tableString.
     *
     * @return string
     */
    public function getTableString()
    {
        return $this->tableString;
    }

    /**
     * Set fieldString.
     *
     * @param string $fieldString
     *
     * @return SelectionCriteria
     */
    public function setFieldString($fieldString)
    {
        $this->fieldString = $fieldString;

        return $this;
    }

    /**
     * Get fieldString.
     *
     * @return string
     */
    public function getFieldString()
    {
        return $this->fieldString;
    }

    /**
     * Set valueString.
     *
     * @param string $valueString
     *
     * @return SelectionCriteria
     */
    public function setValueString($valueString)
    {
        $this->valueString = $valueString;

        return $this;
    }

    /**
     * Get valueString.
     *
     * @return string
     */
    public function getValueString()
    {
        return $this->valueString;
    }

    /**
     * Set conditionString.
     *
     * @param string|null $conditionString
     *
     * @return SelectionCriteria
     */
    public function setConditionString(?string $conditionString): self
    {
        $this->conditionString = $conditionString;

        return $this;
    }

    /**
     * Get conditionString.
     *
     * @return string|null
     */
    public function getConditionString(): ?string
    {
        return $this->conditionString;
    }

    /**
     * Set target.
     *
     * @param string|null $target
     *
     * @return SelectionCriteria
     */
    public function setTarget($target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return string|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set idField.
     *
     * @param int|null $idField
     *
     * @return SelectionCriteria
     */
    public function setIdField($idField = null)
    {
        $this->idField = $idField;

        return $this;
    }

    /**
     * Get idField.
     *
     * @return int|null
     */
    public function getIdField()
    {
        return $this->idField;
    }

    /**
     * @param int $groupNumber
     *
     * @return SelectionCriteria
     */
    public function setGroupNumber($groupNumber): self
    {
        $this->groupNumber = (int) $groupNumber;

        return $this;
    }

    /**
     * @return int
     */
    public function getGroupNumber(): int
    {
        return $this->groupNumber;
    }

    /**
     * @return AssistanceSelection
     */
    public function getAssistanceSelection(): AssistanceSelection
    {
        return $this->assistanceSelection;
    }

    /**
     * @param AssistanceSelection $assistanceSelection
     */
    public function setAssistanceSelection(AssistanceSelection $assistanceSelection): self
    {
        $this->assistanceSelection = $assistanceSelection;

        return $this;
    }
}
