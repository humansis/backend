<?php

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

class VendorCreateInputType extends VendorUpdateInputType
{
    #[Assert\Type('integer')]
    #[Assert\NotNull]
    private $userId;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}
