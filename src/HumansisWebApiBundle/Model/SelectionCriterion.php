<?php
/**
 * SelectionCriterion
 *
 * PHP version 7.1.3
 *
 * @category Class
 * @package  Humansis\WebApi\Model
 * @author   OpenAPI Generator team
 * @link     https://github.com/openapitools/openapi-generator
 */

/**
 * Humansis Web App
 *
 * This is an API documentation for Humansis Web App.
 *
 * The version of the OpenAPI document: 0.1.0
 * 
 * Generated by: https://github.com/openapitools/openapi-generator.git
 *
 */

/**
 * NOTE: This class is auto generated by the openapi generator program.
 * https://github.com/openapitools/openapi-generator
 * Do not edit the class manually.
 */

namespace Humansis\WebApi\Model;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class representing the SelectionCriterion model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class SelectionCriterion 
{
        /**
     * @var int|null
     * @SerializedName("id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $id;

    /**
     * @var int|null
     * @SerializedName("group")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $group;

    /**
     * @var string|null
     * @SerializedName("target")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $target;

    /**
     * @var string|null
     * @SerializedName("field")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $field;

    /**
     * @var string|null
     * @SerializedName("condition")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $condition;

    /**
     * @var string|null
     * @SerializedName("value")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $value;

    /**
     * @var int|null
     * @SerializedName("weight")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $weight;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->group = isset($data['group']) ? $data['group'] : null;
        $this->target = isset($data['target']) ? $data['target'] : null;
        $this->field = isset($data['field']) ? $data['field'] : null;
        $this->condition = isset($data['condition']) ? $data['condition'] : null;
        $this->value = isset($data['value']) ? $data['value'] : null;
        $this->weight = isset($data['weight']) ? $data['weight'] : null;
    }

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets id.
     *
     * @param int|null $id
     *
     * @return $this
     */
    public function setId($id = null)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets group.
     *
     * @return int|null
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Sets group.
     *
     * @param int|null $group
     *
     * @return $this
     */
    public function setGroup($group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Gets target.
     *
     * @return string|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Sets target.
     *
     * @param string|null $target
     *
     * @return $this
     */
    public function setTarget($target = null)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Gets field.
     *
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets field.
     *
     * @param string|null $field
     *
     * @return $this
     */
    public function setField($field = null)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Gets condition.
     *
     * @return string|null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Sets condition.
     *
     * @param string|null $condition
     *
     * @return $this
     */
    public function setCondition($condition = null)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Gets value.
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets value.
     *
     * @param string|null $value
     *
     * @return $this
     */
    public function setValue($value = null)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets weight.
     *
     * @return int|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Sets weight.
     *
     * @param int|null $weight
     *
     * @return $this
     */
    public function setWeight($weight = null)
    {
        $this->weight = $weight;

        return $this;
    }
}


