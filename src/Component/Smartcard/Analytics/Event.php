<?php

declare(strict_types=1);

namespace Component\Smartcard\Analytics;

use DateTimeInterface;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Entity\Assistance\ReliefPackage;
use Entity\Project;
use Entity\Smartcard;
use Entity\SmartcardDeposit;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use JsonSerializable;

class Event implements JsonSerializable
{
    /**
     * @param object[] $linkedObjects
     */
    public function __construct(private readonly string $subject, private readonly string $action, private readonly DateTimeInterface $when, private readonly array $linkedObjects = [], private readonly array $additionalData = [])
    {
    }

    protected function getSubject(): string
    {
        return $this->subject;
    }

    protected function getAction(): string
    {
        return $this->action;
    }

    public function getWhen(): DateTimeInterface
    {
        return $this->when;
    }

    public function jsonSerialize(): array
    {
        $serializedData = [
            'subject' => $this->getSubject(),
            'action' => $this->getAction(),
            'date' => $this->getWhen()->format('Y-m-d H:i'),
        ];
        foreach ($this->linkedObjects as $object) {
            if ($object == null) {
                continue;
            }
            switch ($object::class) {
                case Assistance::class:
                    $serializedData['assistanceId'] = $object->getId();
                    $serializedData['assistanceName'] = $object->getName();
                    break;
                case AssistanceBeneficiary::class:
                    $serializedData['assistanceBeneficiaryId'] = $object->getId();
                    break;
                case ReliefPackage::class:
                    $serializedData['reliefPackageId'] = $object->getId();
                    break;
                case SmartcardDeposit::class:
                    $serializedData['depositId'] = $object->getId();
                    break;
                case SmartcardPurchase::class:
                    $serializedData['purchaseId'] = $object->getId();
                    break;
                case Smartcard::class:
                    $serializedData['smartcardId'] = $object->getId();
                    $serializedData['smartcardSerialNumber'] = $object->getSerialNumber();
                    break;
                case Vendor::class:
                    $serializedData['vendorId'] = $object->getId();
                    $serializedData['vendorName'] = $object->getName();
                    break;
                case Project::class:
                    $serializedData['projectId'] = $object->getId();
                    $serializedData['projectName'] = $object->getName();
                    break;
            }
        }
        foreach ($this->additionalData as $key => $value) {
            $serializedData[$key] = $value;
        }

        return $serializedData;
    }
}
