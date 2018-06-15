<?php

namespace DistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SelectionCriteria
 *
 * @ORM\Table(name="selection_criteria")
 * @ORM\Entity(repositoryClass="DistributionBundle\Repository\SelectionCriteriaRepository")
 */
class SelectionCriteria
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
     * @var string
     *
     * @ORM\Column(name="table_string", type="string", length=255)
     */
    private $tableString;

    /**
     * @var string
     *
     * @ORM\Column(name="field_string", type="string", length=255)
     */
    private $fieldString;

    /**
     * @var string
     *
     * @ORM\Column(name="value_string", type="string", length=255)
     */
    private $valueString;

    /**
     * @var string
     *
     * @ORM\Column(name="condition_string", type="string", length=255)
     */
    private $conditionString;

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
     * Set tableString.
     *
     * @param string $tableString
     *
     * @return SelectionCriteria
     */
    public function setTableString($tableString)
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
     * @param string $conditionString
     *
     * @return SelectionCriteria
     */
    public function setConditionString($conditionString)
    {
        $this->conditionString = $conditionString;

        return $this;
    }

    /**
     * Get conditionString.
     *
     * @return string
     */
    public function getConditionString()
    {
        return $this->conditionString;
    }
}
