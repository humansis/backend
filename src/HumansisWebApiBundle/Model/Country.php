<?php
/**
 * Country
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
 * Class representing the Country model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class Country 
{
        /**
     * English name of Country
     *
     * @var string|null
     * @SerializedName("name")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $name;

    /**
     * Unique ISO code of country
     *
     * @var string|null
     * @SerializedName("iso3")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 3
     * )
     */
    protected $iso3;

    /**
     * ISO code of currency
     *
     * @var string|null
     * @SerializedName("currency")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 3
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
        $this->iso3 = isset($data['iso3']) ? $data['iso3'] : null;
        $this->currency = isset($data['currency']) ? $data['currency'] : null;
    }

    /**
     * Gets name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name.
     *
     * @param string|null $name  English name of Country
     *
     * @return $this
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets iso3.
     *
     * @return string|null
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * Sets iso3.
     *
     * @param string|null $iso3  Unique ISO code of country
     *
     * @return $this
     */
    public function setIso3($iso3 = null)
    {
        $this->iso3 = $iso3;

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
     * @param string|null $currency  ISO code of currency
     *
     * @return $this
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency;

        return $this;
    }
}


