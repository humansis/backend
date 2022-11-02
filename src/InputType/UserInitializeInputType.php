<?php

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserInitializeInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\Length(min=2, max=180)
     */
    protected $username;

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
}
