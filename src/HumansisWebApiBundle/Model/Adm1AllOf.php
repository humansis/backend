<?php
/**
 * Adm1AllOf
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
 * Class representing the Adm1AllOf model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class Adm1AllOf 
{
        /**
     * Unique ISO code of country
     *
     * @var string|null
     * @SerializedName("countryIso3")
     * @Assert\Type("string")
     * @Type("string")
     * @Assert\Length(
     *   max = 3
     * )
     */
    protected $countryIso3;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->countryIso3 = isset($data['countryIso3']) ? $data['countryIso3'] : null;
    }

    /**
     * Gets countryIso3.
     *
     * @return string|null
     */
    public function getCountryIso3()
    {
        return $this->countryIso3;
    }

    /**
     * Sets countryIso3.
     *
     * @param string|null $countryIso3  Unique ISO code of country
     *
     * @return $this
     */
    public function setCountryIso3($countryIso3 = null)
    {
        $this->countryIso3 = $countryIso3;

        return $this;
    }
}


