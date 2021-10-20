<?php

namespace VoucherBundle\Model;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;
use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\Voucher;
use VoucherBundle\Entity\VoucherPurchase;
use VoucherBundle\InputType\SmartcardPurchase as SmartcardPurchaseInput;
use VoucherBundle\InputType\VoucherPurchase as VoucherPurchaseInput;

class PurchaseService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param VoucherPurchaseInput $input
     *
     * @return VoucherPurchase
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
     * @param Smartcard              $smartcard
     * @param SmartcardPurchaseInput $input
     *
     * @return SmartcardPurchase
     *
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    public function purchaseSmartcard(Smartcard $smartcard, SmartcardPurchaseInput $input): SmartcardPurchase
    {
        $hash = $this->hashPurchase($smartcard->getBeneficiary(), $this->getVendor($input->getVendorId()), $input->getCreatedAt());
        $purchaseRepository = $this->em->getRepository(SmartcardPurchase::class);

        /** @var SmartcardPurchase $purchase */
        $purchase = $purchaseRepository->findOneBy(['hash' => $hash]);

        if ($purchase) {
            $this->logger->info("Purchase was already set. [purchaseId: {$purchase->getId()}]");

            return $purchase;
        }

        $purchase = SmartcardPurchase::create($smartcard, $this->getVendor($input->getVendorId()), $input->getCreatedAt());
        $purchase->setHash($hash);

        foreach ($input->getProducts() as $item) {
            $product = $this->getProduct($item['id']);
            $purchase->addRecord($product, $item['quantity'], $item['value'], $item['currency']);
        }

        $smartcard->addPurchase($purchase);

        $this->em->persist($purchase);
        $this->em->flush();

        return $purchase;
    }

    /**
     * @param Beneficiary|null   $beneficiary
     * @param Vendor             $vendor
     * @param DateTimeInterface $createdAt
     *
     * @return string
     */
    private function hashPurchase(?Beneficiary $beneficiary, Vendor $vendor, DateTimeInterface $createdAt): string
    {
        $stringToHash = ($beneficiary ? $beneficiary->getId() : null).$vendor->getId().$createdAt->getTimestamp();

        return md5($stringToHash);
    }

    /**
     * @param $id
     *
     * @return Vendor
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
     * @return Product
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
     * @return Voucher
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
     * @param Booklet $booklet
     *
     * @return bool
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
