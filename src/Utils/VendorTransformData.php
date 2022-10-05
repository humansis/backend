<?php declare(strict_types=1);

namespace Utils;


class VendorTransformData
{
    /**
     * Returns an array representation of beneficiaries in order to prepare the export
     *
     * @param $vendors
     *
     * @return array
     */
    public function transformData($vendors): array
    {
        $exportableTable = [];
        foreach ($vendors as $vendor) {
            $adm1 = $vendor->getLocation() ? $vendor->getLocation()->getAdm1Name() : null;
            $adm2 = $vendor->getLocation() ? $vendor->getLocation()->getAdm2Name() : null;
            $adm3 = $vendor->getLocation() ? $vendor->getLocation()->getAdm3Name() : null;
            $adm4 = $vendor->getLocation() ? $vendor->getLocation()->getAdm4Name() : null;

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
