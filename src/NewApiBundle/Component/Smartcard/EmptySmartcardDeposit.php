<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard;

use DistributionBundle\Entity\Assistance;
use NewApiBundle\Enum\ModalityType;

/**
 * Create empty (virtual) deposit.
 *
 * It is useful for processing deposits which are not synced into the system.
 */
class EmptySmartcardDeposit
{
    protected $value;

    public function __construct(Assistance $assistance)
    {
        foreach ($assistance->getCommodities() as $commodity) {
            if (ModalityType::SMART_CARD === $commodity->getModalityType()) {
                $this->value = $commodity->getValue();
                break;
            }
        }
    }

    public function getValue()
    {
        return $this->getValue();
    }
}
