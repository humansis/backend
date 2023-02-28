<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class BenefciaryPatchInputType implements InputTypeInterface
{
    #[Assert\Choice(callback: [\Entity\Referral::class, 'types'])]
    #[Assert\Length(max: 255)]
    private $referralType;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $referralComment;

    /**
     * @return string|null
     */
    public function getReferralType()
    {
        return $this->referralType;
    }

    /**
     * @param string|null $referralType
     */
    public function setReferralType($referralType)
    {
        $this->referralType = $referralType;
    }

    /**
     * @return string|null
     */
    public function getReferralComment()
    {
        return $this->referralComment;
    }

    /**
     * @param string|null $referralComment
     */
    public function setReferralComment($referralComment)
    {
        $this->referralComment = $referralComment;
    }
}
