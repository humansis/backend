<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

use DateTimeInterface;

class Event implements \JsonSerializable
{
    /** @var string what is about, assistance|purchase|vendor|... */
    private $subject;
    /** @var string what was happened, created|sync|closed|... */
    private $action;
    /** @var DateTimeInterface when was it happened */
    private $when;

    private $additionalData;

    /**
     * @param string            $subject
     * @param string            $action
     * @param DateTimeInterface $when
     * @param array             $additionalData
     */
    public function __construct(string $subject, string $action, DateTimeInterface $when, array $additionalData = [])
    {
        $this->subject = $subject;
        $this->action = $action;
        $this->when = $when;
        $this->additionalData = $additionalData;
    }

    /**
     * @return string
     */
    protected function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    protected function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return DateTimeInterface
     */
    public function getWhen(): DateTimeInterface
    {
        return $this->when;
    }

    public function jsonSerialize(): array
    {
        return array_merge([
            'subject' => $this->getSubject(),
            'action' => $this->getAction(),
            'date' => $this->getWhen()->format('Y-m-d H:i'),
        ], $this->additionalData);
    }

}
