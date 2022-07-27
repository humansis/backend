<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Exception;

use Throwable;
use VoucherBundle\Entity\Smartcard;

/**
 * @deprecated Remove after implement symfony/workflow
 */
class SmartcardNotAllowedStateTransition extends SmartcardException
{
    /**
     * @var string
     */
    private $newState;

    public function __construct(Smartcard $smartcard, string $newState, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($smartcard, $message, $code, $previous);
        $this->newState = $newState;
    }

    /**
     * @return string
     */
    public function getNewState(): string
    {
        return $this->newState;
    }
}
