<?php

namespace VoucherBundle\Utils;

use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\InputType\SmartcardPurchase as SmartcardPurchaseInput;
use VoucherBundle\InputType\SmartcardPurchaseDeprecated as SmartcardPurchaseDeprecatedInput;
use VoucherBundle\Model\PurchaseService;

class SmartcardService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var PurchaseService */
    private $purchaseService;

    public function __construct(EntityManagerInterface $em, PurchaseService $purchaseService)
    {
        $this->em = $em;
        $this->purchaseService = $purchaseService;
    }

    public function purchase(string $serialNumber, $data): SmartcardPurchase
    {
        if ($data instanceof SmartcardPurchaseInput && $data instanceof SmartcardPurchaseDeprecatedInput) {
            throw new \InvalidArgumentException('Argument 3 must be of type '.SmartcardPurchaseInput::class.' or '.SmartcardPurchaseDeprecatedInput::class);
        }

        if ($data instanceof SmartcardPurchaseDeprecatedInput) {
            $products = [];
            foreach ($data->getProducts() as $product) {
                $product['currency'] = null;
                $products[] = $product;
            }

            $new = new SmartcardPurchaseInput();
            $new->setCreatedAt($data->getCreatedAt());
            $new->setVendorId($data->getVendorId());
            $new->setProducts($products);

            $data = $new;
        }

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if (!$smartcard) {
            $smartcard = $this->createSuspiciousSmartcard($serialNumber, $data->getCreatedAt());
        }

        return $this->purchaseService->purchaseSmartcard($smartcard, $data);
    }

    protected function createSuspiciousSmartcard(string $serialNumber, DateTimeInterface $createdAt): Smartcard
    {
        $smartcard = new Smartcard($serialNumber, $createdAt);
        $smartcard->setState(Smartcard::STATE_ACTIVE);
        $smartcard->setSuspicious(true, 'Smartcard does not exists in database');

        $this->em->persist($smartcard);
        $this->em->flush();

        return $smartcard;
    }
}
