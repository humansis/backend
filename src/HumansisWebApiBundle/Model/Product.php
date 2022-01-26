<?php
/**
 * Product
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
 * Class representing the Product model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class Product 
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
     * @var string
     * @SerializedName("image")
     * @Assert\NotNull()
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $image;

    /**
     * @var int
     * @SerializedName("productCategoryId")
     * @Assert\NotNull()
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $productCategoryId;

    /**
     * Unique ISO code of country
     *
     * @var string
     * @SerializedName("iso3")
     * @Assert\NotNull()
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 3
     * )
     */
    protected $iso3;

    /**
     * @var int|null
     * @SerializedName("id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $id;

    /**
     * @var string|null
     * @SerializedName("unit")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 20
     * )
     */
    protected $unit;

    /**
     * @var float|null
     * @SerializedName("unitPrice")
     * @Assert\Type("float")
     * @Type("float")
     */
    protected $unitPrice;

    /**
     * @var string|null
     * @SerializedName("currency")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 3
     * )
     * @Assert\Length(
     *   min = 3
     * )
     */
    protected $currency;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->image = isset($data['image']) ? $data['image'] : null;
        $this->productCategoryId = isset($data['productCategoryId']) ? $data['productCategoryId'] : null;
        $this->iso3 = isset($data['iso3']) ? $data['iso3'] : null;
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->unit = isset($data['unit']) ? $data['unit'] : null;
        $this->unitPrice = isset($data['unitPrice']) ? $data['unitPrice'] : null;
        $this->currency = isset($data['currency']) ? $data['currency'] : null;
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
     * Gets image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Sets image.
     *
     * @param string $image
     *
     * @return $this
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets productCategoryId.
     *
     * @return int
     */
    public function getProductCategoryId()
    {
        return $this->productCategoryId;
    }

    /**
     * Sets productCategoryId.
     *
     * @param int $productCategoryId
     *
     * @return $this
     */
    public function setProductCategoryId($productCategoryId)
    {
        $this->productCategoryId = $productCategoryId;

        return $this;
    }

    /**
     * Gets iso3.
     *
     * @return string
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * Sets iso3.
     *
     * @param string $iso3  Unique ISO code of country
     *
     * @return $this
     */
    public function setIso3($iso3)
    {
        $this->iso3 = $iso3;

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
     * Gets unit.
     *
     * @return string|null
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Sets unit.
     *
     * @param string|null $unit
     *
     * @return $this
     */
    public function setUnit($unit = null)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Gets unitPrice.
     *
     * @return float|null
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * Sets unitPrice.
     *
     * @param float|null $unitPrice
     *
     * @return $this
     */
    public function setUnitPrice($unitPrice = null)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Gets currency.
     *
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Sets currency.
     *
     * @param string|null $currency
     *
     * @return $this
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency;

        return $this;
    }
}


