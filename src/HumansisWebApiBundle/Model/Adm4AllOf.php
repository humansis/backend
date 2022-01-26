<?php
/**
 * Adm4AllOf
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
 * Class representing the Adm4AllOf model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class Adm4AllOf 
{
        /**
     * @var int|null
     * @SerializedName("adm3Id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $adm3Id;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->adm3Id = isset($data['adm3Id']) ? $data['adm3Id'] : null;
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
}


