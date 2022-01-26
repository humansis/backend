<?php
/**
 * LoginResponse
 *
 * PHP version 7.1.3
 *
 * @category Class
 * @package  Humansis\VendorAppApi\Model
 * @author   OpenAPI Generator team
 * @link     https://github.com/openapitools/openapi-generator
 */

/**
 * Humansis Vendor App
 *
 * This is an API documentation for Humansis Vendor App.
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

namespace Humansis\VendorAppApi\Model;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class representing the LoginResponse model.
 *
 * @package Humansis\VendorAppApi\Model
 * @author  OpenAPI Generator team
 */
class LoginResponse 
{
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
     * @SerializedName("username")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $username;

    /**
     * @var string|null
     * @SerializedName("token")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $token;

    /**
     * @var string|null
     * @SerializedName("countryISO3")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $countryISO3;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->username = isset($data['username']) ? $data['username'] : null;
        $this->token = isset($data['token']) ? $data['token'] : null;
        $this->countryISO3 = isset($data['countryISO3']) ? $data['countryISO3'] : null;
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
     * Gets username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets username.
     *
     * @param string|null $username
     *
     * @return $this
     */
    public function setUsername($username = null)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Gets token.
     *
     * @return string|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets token.
     *
     * @param string|null $token
     *
     * @return $this
     */
    public function setToken($token = null)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Gets countryISO3.
     *
     * @return string|null
     */
    public function getCountryISO3()
    {
        return $this->countryISO3;
    }

    /**
     * Sets countryISO3.
     *
     * @param string|null $countryISO3
     *
     * @return $this
     */
    public function setCountryISO3($countryISO3 = null)
    {
        $this->countryISO3 = $countryISO3;

        return $this;
    }
}


