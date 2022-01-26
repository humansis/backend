<?php
/**
 * Project
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
 * Class representing the Project model.
 *
 * @package Humansis\UserAppLegacyApi\Model
 * @author  OpenAPI Generator team
 */
class Project 
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
     * @SerializedName("name")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $name;

    /**
     * @var int|null
     * @SerializedName("number_of_households")
     * @Assert\Type("int")
     * @Type("int")
     * @Assert\GreaterThanOrEqual(0)
     */
    protected $numberOfHouseholds;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->numberOfHouseholds = isset($data['numberOfHouseholds']) ? $data['numberOfHouseholds'] : null;
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
     * @param string|null $name
     *
     * @return $this
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets numberOfHouseholds.
     *
     * @return int|null
     */
    public function getNumberOfHouseholds()
    {
        return $this->numberOfHouseholds;
    }

    /**
     * Sets numberOfHouseholds.
     *
     * @param int|null $numberOfHouseholds
     *
     * @return $this
     */
    public function setNumberOfHouseholds($numberOfHouseholds = null)
    {
        $this->numberOfHouseholds = $numberOfHouseholds;

        return $this;
    }
}


