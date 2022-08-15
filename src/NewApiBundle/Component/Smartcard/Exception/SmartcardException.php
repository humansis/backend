<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Exception;

use Throwable;
use NewApiBundle\Entity\Smartcard;

class SmartcardException extends \Exception
{
    /**
     * @var Smartcard|null
     */
    private $smartcard;

    public function __construct(?Smartcard $smartcard = null, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->smartcard = $smartcard;
    }

    /**
     * @return Smartcard|null
     */
    public function getSmartcard(): ?Smartcard
    {
        return $this->smartcard;
    }

}
