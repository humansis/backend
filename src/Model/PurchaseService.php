<?php

namespace Model;

use Entity\Beneficiary;
use DateTimeInterface;
use Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use InputType\PurchaseProductInputType;
use InputType\SmartcardPurchaseInputType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Entity\Booklet;
use Entity\Product;
use Entity\Smartcard;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Entity\Voucher;
use Entity\VoucherPurchase;
use InputType\SmartcardPurchase as SmartcardPurchaseInput;
use InputType\VoucherPurchase as VoucherPurchaseInput;

class PurchaseService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly LoggerInterface $logger)
    {
    }

    /**
     *
     *
     * @throws EntityNotFoundException
     */
    public function purchase(VoucherPurchaseInput $input): VoucherPurchase
    {
        $voucherPurchase = VoucherPurchase::create(
            $this->getVendor($input->getVendorId()),
            $input->getCreatedAt()
        );

        foreach ($input->getVouchers() as $id) {
            $voucher = $this->getVoucher($id);
            $voucherPurchase->addVoucher($voucher);
        }

        foreach ($input->getProducts() as $item) {
            $product = $this->getProduct($item['id']);
            $voucherPurchase->addRecord($product, $item['quantity'], $item['value']);
        }

        $this->em->persist($voucherPurchase);
        $this->em->flush();

        $this->markAsUsed($voucherPurchase);

        return $voucherPurchase;
    }

    /**
     * @param SmartcardPurchaseInputType|SmartcardPurchaseInput $input
     *
     *
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function purchaseSmartcard(Smartcard $smartcard, \InputType\SmartcardPurchaseInputType|SmartcardPurchaseInput $input): SmartcardPurchase
    {
        $hash = $this->hashPurchase(
            $smartcard->getBeneficiary(),
            $this->getVendor($input->getVendorId()),
            $input->getCreatedAt()
        );
        $purchaseRepository = $this->em->getRepository(SmartcardPurchase::class);

        /** @var SmartcardPurchase $purchase */
        $purchase = $purchaseRepository->findOneBy(['hash' => $hash]);

        if ($purchase) {
            $this->logger->info("Purchase was already set. [purchaseId: {$purchase->getId()}]");

            return $purchase;
        }

        $assistance = null;
        if ($input instanceof SmartcardPurchaseInputType) {
            $assistanceRepository = $this->em->getRepository(Assistance::class);

            /** @var Assistance|null $assistance */
            $assistance = $assistanceRepository->find($input->getAssistanceId());
            if (!$assistance) {
                throw new NotFoundHttpException('Assistance ID must exists');
            }
        }

        $purchase = SmartcardPurchase::create(
            $smartcard,
            $this->getVendor($input->getVendorId()),
            $input->getCreatedAt(),
            $assistance
        );
        $purchase->setHash($hash);

        if ($input instanceof SmartcardPurchaseInput) {
            foreach ($input->getProducts() as $item) {
                $product = $this->getProduct($item['id']);
                $purchase->addRecord($product, $item['quantity'], $item['value'], $item['currency']);
            }
        } else {
            /** @var PurchaseProductInputType $item */
            foreach ($input->getProducts() as $item) {
                $product = $this->getProduct($item->getId());
                $purchase->addRecord($product, $item->getQuantity(), $item->getValue(), $item->getCurrency());
            }
        }

        $smartcard->addPurchase($purchase);

        $this->em->persist($purchase);
        $this->em->flush();

        return $purchase;
    }

    public function hashPurchase(?Beneficiary $beneficiary, Vendor $vendor, DateTimeInterface $createdAt): string
    {
        $stringToHash = ($beneficiary?->getId()) . $vendor->getId() . $createdAt->getTimestamp();

        return md5($stringToHash);
    }

    /**
     * @param $id
     *
     *
     * @throws EntityNotFoundException
     */
    private function getVendor($id): Vendor
    {
        /** @var Vendor $vendor */
        $vendor = $this->em->getRepository(Vendor::class)->find($id);
        if (null === $vendor) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Vendor::class, (array) $id);
        }

        return $vendor;
    }

    /**
     * @param $id
     *
     *
     * @throws EntityNotFoundException
     */
    private function getProduct($id): Product
    {
        /** @var Product $product */
        $product = $this->em->getRepository(Product::class)->find($id);
        if (null === $product) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Product::class, (array) $id);
        }

        return $product;
    }

    /**
     * @param $id
     *
     *
     * @throws EntityNotFoundException
     */
    private function getVoucher($id): Voucher
    {
        /** @var Voucher $voucher */
        $voucher = $this->em->getRepository(Voucher::class)->find($id);
        if (null === $voucher) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(Voucher::class, (array) $id);
        }

        return $voucher;
    }

    protected function markAsUsed(VoucherPurchase $voucherPurchase)
    {
        // find all booklets with all its vouchers used
        foreach ($voucherPurchase->getVouchers() as $voucher) {
            if ($this->isUsed($voucher->getBooklet())) {
                $voucher->getBooklet()->setStatus(Booklet::USED);
                $this->em->persist($voucher);
            }
        }

        $this->em->flush();
    }

    /**
     * Check, if booklet have all its voucher used.
     * Vouchers are used, if it's in some purchase.
     *
     *
     */
    private function isUsed(Booklet $booklet): bool
    {
        foreach ($booklet->getVouchers() as $voucher) {
            if (null === $voucher->getVoucherPurchase()) {
                return false;
            }
        }

        return true;
    }
}
