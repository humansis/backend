<?php

declare(strict_types=1);

namespace Entity\Assistance;

use Entity\AssistanceSelection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Entity\CountrySpecific;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\SelectionCriteriaField;
use Enum\VulnerabilityCriteria;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * SelectionCriteria - user filled criteria
 */
#[ORM\Table(name: 'selection_criteria')]
#[ORM\Entity(repositoryClass: 'Repository\SelectionCriteriaRepository')]
#[ORM\HasLifecycleCallbacks]
class SelectionCriteria
{
    use StandardizedPrimaryKey;

    #[ORM\ManyToOne(targetEntity: 'Entity\AssistanceSelection', inversedBy: 'selectionCriteria')]
    #[ORM\JoinColumn(name: 'assistance_selection_id', nullable: false)]
    private AssistanceSelection|null $assistanceSelection = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'table_string', type: 'string', length: 255)]
    private ?string $tableString = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'target', type: 'string', length: 255, nullable: true)]
    private string $target;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'field_string', type: 'string', length: 255, nullable: true)]
    private string $fieldString;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'field_id', type: 'integer', nullable: true)]
    private int $idField;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'condition_string', type: 'string', length: 255, nullable: true)]
    private ?string $conditionString = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'value_string', type: 'string', length: 255, nullable: true)]
    private string|null $valueString = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'weight', type: 'integer')]
    private ?int $weight = null;

    #[SymfonyGroups(['FullAssistance', 'SmallAssistance'])]
    #[ORM\Column(name: 'group_number', type: 'integer', nullable: false)]
    private ?int $groupNumber = null;

    private bool $deprecated = true;

    #[ORM\PostLoad]
    public function postLoad(LifecycleEventArgs $lifecycleEventArgs): void
    {
        if ($this->tableString === SelectionCriteriaField::COUNTRY_SPECIFIC) {
            $iso3 = $this->assistanceSelection->getAssistance()->getProject()->getCountryIso3();
            $this->deprecated = $lifecycleEventArgs->getObjectManager()
                    ->getRepository(CountrySpecific::class)
                    ->findOneBy(['fieldString' => $this->fieldString, 'countryIso3' => $iso3]) === null;
        } elseif ($this->tableString === SelectionCriteriaField::VULNERABILITY_CRITERIA) {
            $this->deprecated = !in_array($this->fieldString, VulnerabilityCriteria::values());
        } else {
            $this->deprecated = !in_array($this->fieldString, SelectionCriteriaField::values());
        }
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight)
    {
        $this->weight = $weight;
    }

    /**
     * Set tableString.
     *
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

    public function setValueString(string|null $valueString): self
    {
        $this->valueString = $valueString;

        return $this;
    }

    public function getValueString(): string|null
    {
        return $this->valueString;
    }

    /**
     * Set conditionString.
     *
     *
     */
    public function setConditionString(?string $conditionString): self
    {
        $this->conditionString = $conditionString;

        return $this;
    }

    /**
     * Get conditionString.
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
     */
    public function setGroupNumber($groupNumber): self
    {
        $this->groupNumber = (int) $groupNumber;

        return $this;
    }

    public function getGroupNumber(): int
    {
        return $this->groupNumber;
    }

    public function getAssistanceSelection(): AssistanceSelection
    {
        return $this->assistanceSelection;
    }

    public function setAssistanceSelection(AssistanceSelection $assistanceSelection): self
    {
        $this->assistanceSelection = $assistanceSelection;

        return $this;
    }
}
