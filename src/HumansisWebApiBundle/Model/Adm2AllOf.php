<?php
/**
 * Adm2AllOf
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
 * Class representing the Adm2AllOf model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class Adm2AllOf 
{
        /**
     * @var int|null
     * @SerializedName("adm1Id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $adm1Id;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->adm1Id = isset($data['adm1Id']) ? $data['adm1Id'] : null;
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
}


