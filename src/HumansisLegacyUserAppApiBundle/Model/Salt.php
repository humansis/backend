<?php
/**
 * Salt
 *
 * PHP version 7.1.3
 *
 * @category Class
 * @package  Humansis\UserAppLegacyApi\Model
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

namespace Humansis\UserAppLegacyApi\Model;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

/**
 * Class representing the Salt model.
 *
 * @package Humansis\UserAppLegacyApi\Model
 * @author  OpenAPI Generator team
 */
class Salt 
{
        /**
     * not used by the application
     *
     * @var int|null
     * @SerializedName("user_id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $userId;

    /**
     * @var string|null
     * @SerializedName("salt")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $salt;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->userId = isset($data['userId']) ? $data['userId'] : null;
        $this->salt = isset($data['salt']) ? $data['salt'] : null;
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
     * @param int|null $userId  not used by the application
     *
     * @return $this
     */
    public function setUserId($userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Gets salt.
     *
     * @return string|null
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Sets salt.
     *
     * @param string|null $salt
     *
     * @return $this
     */
    public function setSalt($salt = null)
    {
        $this->salt = $salt;

        return $this;
    }
}


