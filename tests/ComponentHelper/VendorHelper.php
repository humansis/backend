<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use Doctrine\ORM\EntityManagerInterface;
use Entity\Vendor;
use Exception;
use InputType\VendorCreateInputType;
use Utils\ValueGenerator\ValueGenerator;
use Utils\VendorService;

/**
 * @property EntityManagerInterface $em
 */
trait VendorHelper
{
    /**
     * @throws Exception
     */
    public function createVendor(VendorCreateInputType $vendorCreateInputType, VendorService $vendorService): Vendor
    {
        /**
         * @var Vendor|null $vendor
         */
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy(['user' => $vendorCreateInputType->getUserId()]);
        if ($vendor) {
            return $vendor;
        } else {
            return $vendorService->create($vendorCreateInputType);
        }
    }

    public function buildVendorInputType(int $locationId, int $userId): VendorCreateInputType
    {
        $vendorInputType = new VendorCreateInputType();
        $vendorInputType->setName('Dummy Vendor ' . ValueGenerator::int(1, 1000));
        $vendorInputType->setLocationId($locationId);
        $vendorInputType->setUserId($userId);

        return $vendorInputType;
    }
}
