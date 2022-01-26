<?php
/**
 * Vendor
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
 * Class representing the Vendor model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class Vendor 
{
        /**
     * @var string
     * @SerializedName("name")
     * @Assert\NotNull()
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $name;

    /**
     * Primary identifier
     *
     * @var int|null
     * @SerializedName("id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $id;

    /**
     * @var string|null
     * @SerializedName("shop")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $shop;

    /**
     * @var string|null
     * @SerializedName("addressStreet")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $addressStreet;

    /**
     * @var string|null
     * @SerializedName("addressNumber")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $addressNumber;

    /**
     * @var string|null
     * @SerializedName("addressPostcode")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $addressPostcode;

    /**
     * @var int|null
     * @SerializedName("locationId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $locationId;

    /**
     * @var int|null
     * @SerializedName("userId")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $userId;

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
     * @SerializedName("vendorNo")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $vendorNo;

    /**
     * @var string|null
     * @SerializedName("contractNo")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 255
     * )
     */
    protected $contractNo;

    /**
     * @var bool|null
     * @SerializedName("canSellFood")
     * @Assert\Type("bool")
     * @Type("bool")
     */
    protected $canSellFood;

    /**
     * @var bool|null
     * @SerializedName("canSellNonFood")
     * @Assert\Type("bool")
     * @Type("bool")
     */
    protected $canSellNonFood;

    /**
     * @var bool|null
     * @SerializedName("canSellCashback")
     * @Assert\Type("bool")
     * @Type("bool")
     */
    protected $canSellCashback;

    /**
     * @var bool|null
     * @SerializedName("canDoRemoteDistributions")
     * @Assert\Type("bool")
     * @Type("bool")
     */
    protected $canDoRemoteDistributions;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->shop = isset($data['shop']) ? $data['shop'] : null;
        $this->addressStreet = isset($data['addressStreet']) ? $data['addressStreet'] : null;
        $this->addressNumber = isset($data['addressNumber']) ? $data['addressNumber'] : null;
        $this->addressPostcode = isset($data['addressPostcode']) ? $data['addressPostcode'] : null;
        $this->locationId = isset($data['locationId']) ? $data['locationId'] : null;
        $this->userId = isset($data['userId']) ? $data['userId'] : null;
        $this->adm1Id = isset($data['adm1Id']) ? $data['adm1Id'] : null;
        $this->adm2Id = isset($data['adm2Id']) ? $data['adm2Id'] : null;
        $this->adm3Id = isset($data['adm3Id']) ? $data['adm3Id'] : null;
        $this->adm4Id = isset($data['adm4Id']) ? $data['adm4Id'] : null;
        $this->vendorNo = isset($data['vendorNo']) ? $data['vendorNo'] : null;
        $this->contractNo = isset($data['contractNo']) ? $data['contractNo'] : null;
        $this->canSellFood = isset($data['canSellFood']) ? $data['canSellFood'] : true;
        $this->canSellNonFood = isset($data['canSellNonFood']) ? $data['canSellNonFood'] : true;
        $this->canSellCashback = isset($data['canSellCashback']) ? $data['canSellCashback'] : true;
        $this->canDoRemoteDistributions = isset($data['canDoRemoteDistributions']) ? $data['canDoRemoteDistributions'] : false;
    }

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * @param int|null $id  Primary identifier
     *
     * @return $this
     */
    public function setId($id = null)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets shop.
     *
     * @return string|null
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * Sets shop.
     *
     * @param string|null $shop
     *
     * @return $this
     */
    public function setShop($shop = null)
    {
        $this->shop = $shop;

        return $this;
    }

    /**
     * Gets addressStreet.
     *
     * @return string|null
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Sets addressStreet.
     *
     * @param string|null $addressStreet
     *
     * @return $this
     */
    public function setAddressStreet($addressStreet = null)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Gets addressNumber.
     *
     * @return string|null
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * Sets addressNumber.
     *
     * @param string|null $addressNumber
     *
     * @return $this
     */
    public function setAddressNumber($addressNumber = null)
    {
        $this->addressNumber = $addressNumber;

        return $this;
    }

    /**
     * Gets addressPostcode.
     *
     * @return string|null
     */
    public function getAddressPostcode()
    {
        return $this->addressPostcode;
    }

    /**
     * Sets addressPostcode.
     *
     * @param string|null $addressPostcode
     *
     * @return $this
     */
    public function setAddressPostcode($addressPostcode = null)
    {
        $this->addressPostcode = $addressPostcode;

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
     * Gets userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Sets userId.
     *
     * @param int|null $userId
     *
     * @return $this
     */
    public function setUserId($userId = null)
    {
        $this->userId = $userId;

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
     * Gets vendorNo.
     *
     * @return string|null
     */
    public function getVendorNo()
    {
        return $this->vendorNo;
    }

    /**
     * Sets vendorNo.
     *
     * @param string|null $vendorNo
     *
     * @return $this
     */
    public function setVendorNo($vendorNo = null)
    {
        $this->vendorNo = $vendorNo;

        return $this;
    }

    /**
     * Gets contractNo.
     *
     * @return string|null
     */
    public function getContractNo()
    {
        return $this->contractNo;
    }

    /**
     * Sets contractNo.
     *
     * @param string|null $contractNo
     *
     * @return $this
     */
    public function setContractNo($contractNo = null)
    {
        $this->contractNo = $contractNo;

        return $this;
    }

    /**
     * Gets canSellFood.
     *
     * @return bool|null
     */
    public function isCanSellFood()
    {
        return $this->canSellFood;
    }

    /**
     * Sets canSellFood.
     *
     * @param bool|null $canSellFood
     *
     * @return $this
     */
    public function setCanSellFood($canSellFood = null)
    {
        $this->canSellFood = $canSellFood;

        return $this;
    }

    /**
     * Gets canSellNonFood.
     *
     * @return bool|null
     */
    public function isCanSellNonFood()
    {
        return $this->canSellNonFood;
    }

    /**
     * Sets canSellNonFood.
     *
     * @param bool|null $canSellNonFood
     *
     * @return $this
     */
    public function setCanSellNonFood($canSellNonFood = null)
    {
        $this->canSellNonFood = $canSellNonFood;

        return $this;
    }

    /**
     * Gets canSellCashback.
     *
     * @return bool|null
     */
    public function isCanSellCashback()
    {
        return $this->canSellCashback;
    }

    /**
     * Sets canSellCashback.
     *
     * @param bool|null $canSellCashback
     *
     * @return $this
     */
    public function setCanSellCashback($canSellCashback = null)
    {
        $this->canSellCashback = $canSellCashback;

        return $this;
    }

    /**
     * Gets canDoRemoteDistributions.
     *
     * @return bool|null
     */
    public function isCanDoRemoteDistributions()
    {
        return $this->canDoRemoteDistributions;
    }

    /**
     * Sets canDoRemoteDistributions.
     *
     * @param bool|null $canDoRemoteDistributions
     *
     * @return $this
     */
    public function setCanDoRemoteDistributions($canDoRemoteDistributions = null)
    {
        $this->canDoRemoteDistributions = $canDoRemoteDistributions;

        return $this;
    }
}


