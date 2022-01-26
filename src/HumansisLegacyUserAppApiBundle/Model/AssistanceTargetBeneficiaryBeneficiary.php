<?php
/**
 * AssistanceTargetBeneficiaryBeneficiary
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
 * Class representing the AssistanceTargetBeneficiaryBeneficiary model.
 *
 * @package Humansis\UserAppLegacyApi\Model
 * @author  OpenAPI Generator team
 */
class AssistanceTargetBeneficiaryBeneficiary 
{
        /**
     * @var int|null
     * @SerializedName("id")
     * @Assert\Type("int")
     * @Type("int")
     */
    protected $id;

    /**
     * @var string|null
     * @SerializedName("localFamilyName")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $localFamilyName;

    /**
     * @var string|null
     * @SerializedName("localGivenName")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $localGivenName;

    /**
     * @var string|null
     * @SerializedName("referralType")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $referralType;

    /**
     * @var string|null
     * @SerializedName("referralComment")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $referralComment;

    /**
     * @var string|null
     * @SerializedName("nationalCardId")
     * @Assert\Type("string")
     * @Type("string")
     */
    protected $nationalCardId;

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->localFamilyName = isset($data['localFamilyName']) ? $data['localFamilyName'] : null;
        $this->localGivenName = isset($data['localGivenName']) ? $data['localGivenName'] : null;
        $this->referralType = isset($data['referralType']) ? $data['referralType'] : null;
        $this->referralComment = isset($data['referralComment']) ? $data['referralComment'] : null;
        $this->nationalCardId = isset($data['nationalCardId']) ? $data['nationalCardId'] : null;
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
     * Gets localFamilyName.
     *
     * @return string|null
     */
    public function getLocalFamilyName()
    {
        return $this->localFamilyName;
    }

    /**
     * Sets localFamilyName.
     *
     * @param string|null $localFamilyName
     *
     * @return $this
     */
    public function setLocalFamilyName($localFamilyName = null)
    {
        $this->localFamilyName = $localFamilyName;

        return $this;
    }

    /**
     * Gets localGivenName.
     *
     * @return string|null
     */
    public function getLocalGivenName()
    {
        return $this->localGivenName;
    }

    /**
     * Sets localGivenName.
     *
     * @param string|null $localGivenName
     *
     * @return $this
     */
    public function setLocalGivenName($localGivenName = null)
    {
        $this->localGivenName = $localGivenName;

        return $this;
    }

    /**
     * Gets referralType.
     *
     * @return string|null
     */
    public function getReferralType()
    {
        return $this->referralType;
    }

    /**
     * Sets referralType.
     *
     * @param string|null $referralType
     *
     * @return $this
     */
    public function setReferralType($referralType = null)
    {
        $this->referralType = $referralType;

        return $this;
    }

    /**
     * Gets referralComment.
     *
     * @return string|null
     */
    public function getReferralComment()
    {
        return $this->referralComment;
    }

    /**
     * Sets referralComment.
     *
     * @param string|null $referralComment
     *
     * @return $this
     */
    public function setReferralComment($referralComment = null)
    {
        $this->referralComment = $referralComment;

        return $this;
    }

    /**
     * Gets nationalCardId.
     *
     * @return string|null
     */
    public function getNationalCardId()
    {
        return $this->nationalCardId;
    }

    /**
     * Sets nationalCardId.
     *
     * @param string|null $nationalCardId
     *
     * @return $this
     */
    public function setNationalCardId($nationalCardId = null)
    {
        $this->nationalCardId = $nationalCardId;

        return $this;
    }
}


