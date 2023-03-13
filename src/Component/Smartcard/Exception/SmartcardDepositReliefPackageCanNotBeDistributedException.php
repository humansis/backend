<?php

declare(strict_types=1);

namespace Component\Smartcard\Exception;

use Entity\Assistance\ReliefPackage;
use Exception;
use Symfony\Component\Workflow\TransitionBlocker;
use Symfony\Component\Workflow\TransitionBlockerList;

class SmartcardDepositReliefPackageCanNotBeDistributedException extends Exception
{
    public function __construct(ReliefPackage $reliefPackage, TransitionBlockerList $transitionBlockerList)
    {
        parent::__construct(
            "Relief package #{$reliefPackage->getId()} cannot be distributed. State of RP: '{$reliefPackage->getState()}'.
            Workflow blocker messages: [" . $this->getTbMessages($transitionBlockerList) . "]"
        );
    }

    private function getTbMessages(TransitionBlockerList $transitionBlockerList): string
    {
        $tbMessages = [];
        /** @var TransitionBlocker $item */
        foreach ($transitionBlockerList as $item) {
            $tbMessages[] = $item->getMessage();
        }

        return implode(', ', $tbMessages);
    }
}
