<?php
/**
 * DistributedItem
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
 * Class representing the DistributedItem model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class DistributedItem 
{
        /**
     * @var int|null
     * @SerializedName("projectId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $projectId;

    /**
     * @var int|null
     * @SerializedName("beneficiaryId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $beneficiaryId;

    /**
     * @var int|null
     * @SerializedName("assistanceId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $assistanceId;

    /**
     * @var string|null
     * @SerializedName("dateDistribution")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $dateDistribution;

    /**
     * @var int|null
     * @SerializedName("commodityId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $commodityId;

    /**
     * @var float|null
     * @SerializedName("amount")
     * @Assert\Type("float")
     * @Type("float")
     */
    protected $amount;

    /**
     * @var int|null
     * @SerializedName("locationId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $locationId;

    /**
     * @var int|null
     * @SerializedName("adm1Id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $adm1Id;

    /**
     * @var int|null
     * @SerializedName("adm2Id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $adm2Id;

    /**
     * @var int|null
     * @SerializedName("adm3Id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $adm3Id;

    /**
     * @var int|null
     * @SerializedName("adm4Id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $adm4Id;

    /**
     * @var string|null
     * @SerializedName("carrierNumber")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $carrierNumber;

    /**
     * @var string|null
     * @SerializedName("type")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $type;

    /**
     * @var string|null
     * @SerializedName("modalityType")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $modalityType;

    /**
     * ID of User
     *
     * @var int|null
     * @SerializedName("fieldOfficerId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $fieldOfficerId;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->projectId = isset($data['projectId']) ? $data['projectId'] : null;
        $this->beneficiaryId = isset($data['beneficiaryId']) ? $data['beneficiaryId'] : null;
        $this->assistanceId = isset($data['assistanceId']) ? $data['assistanceId'] : null;
        $this->dateDistribution = isset($data['dateDistribution']) ? $data['dateDistribution'] : null;
        $this->commodityId = isset($data['commodityId']) ? $data['commodityId'] : null;
        $this->amount = isset($data['amount']) ? $data['amount'] : null;
        $this->locationId = isset($data['locationId']) ? $data['locationId'] : null;
        $this->adm1Id = isset($data['adm1Id']) ? $data['adm1Id'] : null;
        $this->adm2Id = isset($data['adm2Id']) ? $data['adm2Id'] : null;
        $this->adm3Id = isset($data['adm3Id']) ? $data['adm3Id'] : null;
        $this->adm4Id = isset($data['adm4Id']) ? $data['adm4Id'] : null;
        $this->carrierNumber = isset($data['carrierNumber']) ? $data['carrierNumber'] : null;
        $this->type = isset($data['type']) ? $data['type'] : null;
        $this->modalityType = isset($data['modalityType']) ? $data['modalityType'] : null;
        $this->fieldOfficerId = isset($data['fieldOfficerId']) ? $data['fieldOfficerId'] : null;
    }

    /**
     * Gets projectId.
     *
     * @return int|null
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Sets projectId.
     *
     * @param int|null $projectId
     *
     * @return $this
     */
    public function setProjectId($projectId = null)
    {
        $this->projectId = $projectId;

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
     * Gets dateDistribution.
     *
     * @return string|null
     */
    public function getDateDistribution()
    {
        return $this->dateDistribution;
    }

    /**
     * Sets dateDistribution.
     *
     * @param string|null $dateDistribution
     *
     * @return $this
     */
    public function setDateDistribution($dateDistribution = null)
    {
        $this->dateDistribution = $dateDistribution;

        return $this;
    }

    /**
     * Gets commodityId.
     *
     * @return int|null
     */
    public function getCommodityId()
    {
        return $this->commodityId;
    }

    /**
     * Sets commodityId.
     *
     * @param int|null $commodityId
     *
     * @return $this
     */
    public function setCommodityId($commodityId = null)
    {
        $this->commodityId = $commodityId;

        return $this;
    }

    /**
     * Gets amount.
     *
     * @return float|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Sets amount.
     *
     * @param float|null $amount
     *
     * @return $this
     */
    public function setAmount($amount = null)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets locationId.
     *
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Sets locationId.
     *
     * @param int|null $locationId
     *
     * @return $this
     */
    public function setLocationId($locationId = null)
    {
        $this->locationId = $locationId;

        return $this;
    }

    /**
     * Gets adm1Id.
     *
     * @return int|null
     */
    public function getAdm1Id()
    {
        return $this->adm1Id;
    }

    /**
     * Sets adm1Id.
     *
     * @param int|null $adm1Id
     *
     * @return $this
     */
    public function setAdm1Id($adm1Id = null)
    {
        $this->adm1Id = $adm1Id;

        return $this;
    }

    /**
     * Gets adm2Id.
     *
     * @return int|null
     */
    public function getAdm2Id()
    {
        return $this->adm2Id;
    }

    /**
     * Sets adm2Id.
     *
     * @param int|null $adm2Id
     *
     * @return $this
     */
    public function setAdm2Id($adm2Id = null)
    {
        $this->adm2Id = $adm2Id;

        return $this;
    }

    /**
     * Gets adm3Id.
     *
     * @return int|null
     */
    public function getAdm3Id()
    {
        return $this->adm3Id;
    }

    /**
     * Sets adm3Id.
     *
     * @param int|null $adm3Id
     *
     * @return $this
     */
    public function setAdm3Id($adm3Id = null)
    {
        $this->adm3Id = $adm3Id;

        return $this;
    }

    /**
     * Gets adm4Id.
     *
     * @return int|null
     */
    public function getAdm4Id()
    {
        return $this->adm4Id;
    }

    /**
     * Sets adm4Id.
     *
     * @param int|null $adm4Id
     *
     * @return $this
     */
    public function setAdm4Id($adm4Id = null)
    {
        $this->adm4Id = $adm4Id;

        return $this;
    }

    /**
     * Gets carrierNumber.
     *
     * @return string|null
     */
    public function getCarrierNumber()
    {
        return $this->carrierNumber;
    }

    /**
     * Sets carrierNumber.
     *
     * @param string|null $carrierNumber
     *
     * @return $this
     */
    public function setCarrierNumber($carrierNumber = null)
    {
        $this->carrierNumber = $carrierNumber;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets type.
     *
     * @param string|null $type
     *
     * @return $this
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets modalityType.
     *
     * @return string|null
     */
    public function getModalityType()
    {
        return $this->modalityType;
    }

    /**
     * Sets modalityType.
     *
     * @param string|null $modalityType
     *
     * @return $this
     */
    public function setModalityType($modalityType = null)
    {
        $this->modalityType = $modalityType;

        return $this;
    }

    /**
     * Gets fieldOfficerId.
     *
     * @return int|null
     */
    public function getFieldOfficerId()
    {
        return $this->fieldOfficerId;
    }

    /**
     * Sets fieldOfficerId.
     *
     * @param int|null $fieldOfficerId  ID of User
     *
     * @return $this
     */
    public function setFieldOfficerId($fieldOfficerId = null)
    {
        $this->fieldOfficerId = $fieldOfficerId;

        return $this;
    }
}


