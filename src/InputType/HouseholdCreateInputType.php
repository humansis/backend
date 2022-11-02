<?php

declare(strict_types=1);

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

#[Assert\GroupSequenceProvider]
class HouseholdCreateInputType extends HouseholdUpdateInputType implements GroupSequenceProviderInterface
{
    public function getGroupSequence()
    {
        $commonSequence = [
            'HouseholdCreateInputType',
            'Strict',
        ];

        $proxyParameters = [
            $this->getProxyLocalGivenName(),
            $this->getProxyLocalFamilyName(),
            $this->getProxyLocalParentsName(),
            $this->getProxyEnGivenName(),
            $this->getProxyEnFamilyName(),
            $this->getProxyEnParentsName(),
            $this->getProxyPhone(),
            $this->getProxyNationalIdCard(),
        ];

        foreach ($proxyParameters as $proxyParameter) {
            if (null !== $proxyParameter) {
                $commonSequence[] = 'Proxy';
                break;
            }
        }

        return $commonSequence;
    }
}
