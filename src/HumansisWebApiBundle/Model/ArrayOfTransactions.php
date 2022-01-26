<?php
/**
 * ArrayOfTransactions
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
 * Class representing the ArrayOfTransactions model.
 *
 * @package Humansis\WebApi\Model
 * @author  OpenAPI Generator team
 */
class ArrayOfTransactions 
{
        /**
     * @var int|null
     * @SerializedName("totalCount")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $totalCount;

    /**
     * @var Humansis\WebApi\Model\Transaction[]|null
     * @SerializedName("data")
     * @Assert\All({
     *   @Assert\Type("Humansis\WebApi\Model\Transaction")
     * })
     * @Type("array<Humansis\WebApi\Model\Transaction>")
     */
    protected $data;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->totalCount = isset($data['totalCount']) ? $data['totalCount'] : null;
        $this->data = isset($data['data']) ? $data['data'] : null;
    }

    /**
     * Gets totalCount.
     *
     * @return int|null
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * Sets totalCount.
     *
     * @param int|null $totalCount
     *
     * @return $this
     */
    public function setTotalCount($totalCount = null)
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * Gets data.
     *
     * @return Humansis\WebApi\Model\Transaction[]|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Sets data.
     *
     * @param Humansis\WebApi\Model\Transaction[]|null $data
     *
     * @return $this
     */
    public function setData(array $data = null)
    {
        $this->data = $data;

        return $this;
    }
}


