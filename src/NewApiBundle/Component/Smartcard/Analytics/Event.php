<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

use DateTime;

class Event
{
    /** @var string what is about, assistance|purchase|vendor|... */
    private $subject;
    /** @var string what was happened, created|sync|closed|... */
    private $action;
    /** @var DateTime when was it happened */
    private $when;

    /**
     * @param string   $subject
     * @param string   $action
     * @param DateTime $when
     */
    public function __construct(string $subject, string $action, DateTime $when)
    {
        $this->subject = $subject;
        $this->action = $action;
        $this->when = $when;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return DateTime
     */
    public function getWhen(): DateTime
    {
        return $this->when;
    }

}
