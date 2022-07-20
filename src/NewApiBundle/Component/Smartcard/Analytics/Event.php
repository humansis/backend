<?php declare(strict_types=1);

namespace NewApiBundle\Component\Smartcard\Analytics;

use DateTimeInterface;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Project;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;

class Event implements \JsonSerializable
{
    /** @var string what is about, assistance|purchase|vendor|... */
    private $subject;
    /** @var string what was happened, created|sync|closed|... */
    private $action;
    /** @var DateTimeInterface when was it happened */
    private $when;
    /** @var object[] */
    private $linkedObjects;
    /** @var array */
    private $additionalData;

    /**
     * @param string            $subject
     * @param string            $action
     * @param DateTimeInterface $when
     * @param array             $additionalData
     * @param object            $linkedObjects
     */
    public function __construct(string $subject, string $action, DateTimeInterface $when, array $linkedObjects = [], array $additionalData = [])
    {
        $this->subject = $subject;
        $this->action = $action;
        $this->when = $when;
        $this->additionalData = $additionalData;
        $this->linkedObjects = $linkedObjects;
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
        $serializedData = [
            'subject' => $this->getSubject(),
            'action' => $this->getAction(),
            'date' => $this->getWhen()->format('Y-m-d H:i'),
        ];
        foreach ($this->linkedObjects as $object) {
            if ($object == null) continue;
            switch (get_class($object)) {
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
