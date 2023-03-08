<?php

declare(strict_types=1);

namespace Utils;

class VendorTransformData
{
    /**
     * Returns an array representation of beneficiaries in order to prepare the export
     */
    public function transformData(array $vendors): array
    {
        $exportableTable = [];
        foreach ($vendors as $vendor) {
            $adm1 = $vendor->getLocation()?->getAdm1Name();
            $adm2 = $vendor->getLocation()?->getAdm2Name();
            $adm3 = $vendor->getLocation()?->getAdm3Name();
            $adm4 = $vendor->getLocation()?->getAdm4Name();

            $exportableTable [] = [
                "Vendor's name" => $vendor->getUser()->getUsername(),
                "Shop's name" => $vendor->getName(),
                "Shop's type" => $vendor->getShop(),
                "Address number" => $vendor->getAddressNumber(),
                "Address street" => $vendor->getAddressStreet(),
                "Address postcode" => $vendor->getAddressPostcode(),
                'Contract No.' => $vendor->getContractNo(),
                'Vendor No.' => $vendor->getVendorNo(),
                "adm1" => $adm1,
                "adm2" => $adm2,
                "adm3" => $adm3,
                "adm4" => $adm4,
            ];
        }

        return $exportableTable;
    }
}
