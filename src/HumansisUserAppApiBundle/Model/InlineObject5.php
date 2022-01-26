<?php
/**
 * InlineObject5
 *
 * PHP version 7.1.3
 *
 * @category Class
 * @package  Humansis\UserAppApi\Model
 * @author   OpenAPI Generator team
 * @link     https://github.com/openapitools/openapi-generator
 */

/**
 * Humansis Offline App
 *
 * This is an API documentation for Humansis Offine App.
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

namespace Humansis\UserAppApi\Model;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class representing the InlineObject5 model.
 *
 * @package Humansis\UserAppApi\Model
 * @author  OpenAPI Generator team
 */
class InlineObject5 
{
        /**
     * @var int|null
     * @SerializedName("assistanceId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $assistanceId;

    /**
     * @var float|null
     * @SerializedName("value")
     * @Assert\Type("float")
     * @Type("float")
     */
    protected $value;

    /**
     * @var string|null
     * @SerializedName("createdAt")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $createdAt;

    /**
     * @var int|null
     * @SerializedName("beneficiaryId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $beneficiaryId;

    /**
     * @var float|null
     * @SerializedName("balanceBefore")
     * @Assert\Type("float")
     * @Type("float")
     */
    protected $balanceBefore;

    /**
     * @var float|null
     * @SerializedName("balanceAfter")
     * @Assert\Type("float")
     * @Type("float")
     */
    protected $balanceAfter;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->assistanceId = isset($data['assistanceId']) ? $data['assistanceId'] : null;
        $this->value = isset($data['value']) ? $data['value'] : null;
        $this->createdAt = isset($data['createdAt']) ? $data['createdAt'] : null;
        $this->beneficiaryId = isset($data['beneficiaryId']) ? $data['beneficiaryId'] : null;
        $this->balanceBefore = isset($data['balanceBefore']) ? $data['balanceBefore'] : null;
        $this->balanceAfter = isset($data['balanceAfter']) ? $data['balanceAfter'] : null;
    }

    /**
     * Gets assistanceId.
     *
     * @return int|null
     */
    public function getAssistanceId()
    {
        return $this->assistanceId;
    }

    /**
     * Sets assistanceId.
     *
     * @param int|null $assistanceId
     *
     * @return $this
     */
    public function setAssistanceId($assistanceId = null)
    {
        $this->assistanceId = $assistanceId;

        return $this;
    }

    /**
     * Gets value.
     *
     * @return float|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets value.
     *
     * @param float|null $value
     *
     * @return $this
     */
    public function setValue($value = null)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets createdAt.
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets createdAt.
     *
     * @param string|null $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt = null)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets beneficiaryId.
     *
     * @return int|null
     */
    public function getBeneficiaryId()
    {
        return $this->beneficiaryId;
    }

    /**
     * Sets beneficiaryId.
     *
     * @param int|null $beneficiaryId
     *
     * @return $this
     */
    public function setBeneficiaryId($beneficiaryId = null)
    {
        $this->beneficiaryId = $beneficiaryId;

        return $this;
    }

    /**
     * Gets balanceBefore.
     *
     * @return float|null
     */
    public function getBalanceBefore()
    {
        return $this->balanceBefore;
    }

    /**
     * Sets balanceBefore.
     *
     * @param float|null $balanceBefore
     *
     * @return $this
     */
    public function setBalanceBefore($balanceBefore = null)
    {
        $this->balanceBefore = $balanceBefore;

        return $this;
    }

    /**
     * Gets balanceAfter.
     *
     * @return float|null
     */
    public function getBalanceAfter()
    {
        return $this->balanceAfter;
    }

    /**
     * Sets balanceAfter.
     *
     * @param float|null $balanceAfter
     *
     * @return $this
     */
    public function setBalanceAfter($balanceAfter = null)
    {
        $this->balanceAfter = $balanceAfter;

        return $this;
    }
}


