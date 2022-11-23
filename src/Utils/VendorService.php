<?php

namespace Utils;

use Entity\Location;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Exception\ExportNoDataException;
use InputType\VendorCreateInputType;
use InputType\VendorUpdateInputType;
use InvalidArgumentException;
use Repository\LocationRepository;
use Repository\UserRepository;
use Repository\VendorRepository;
use Repository\VoucherPurchaseRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Entity\User;
use Entity\Vendor;

class VendorService
{
    /**
     * UserService constructor.
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly Environment $twig,
        private readonly ExportService $exportService,
        private readonly VendorRepository $vendorRepository,
        private readonly LocationRepository $locationRepository,
        private readonly UserRepository $userRepository,
        private readonly VoucherPurchaseRepository $voucherPurchaseRepository,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function create(VendorCreateInputType $inputType): Vendor
    {
        $user = $this->userRepository->find($inputType->getUserId());

        if (!$user instanceof User) {
            throw new EntityNotFoundException('User with ID #' . $inputType->getUserId() . ' does not exists.');
        }

        $location = $this->locationRepository->find($inputType->getLocationId());

        if (!$location instanceof Location) {
            throw new EntityNotFoundException('Location with ID #' . $inputType->getLocationId() . ' does not exists.');
        }

        if (null !== $user->getVendor()) {
            throw new InvalidArgumentException(
                'User with ID #' . $inputType->getUserId() . ' is already defined as vendor.'
            );
        }

        $vendor = new Vendor();
        $vendor->setName($inputType->getName())
            ->setShop($inputType->getShop())
            ->setAddressStreet($inputType->getAddressStreet())
            ->setAddressNumber($inputType->getAddressNumber())
            ->setAddressPostcode($inputType->getAddressPostcode())
            ->setLocation($location)
            ->setArchived(false)
            ->setUser($user)
            ->setVendorNo($inputType->getVendorNo())
            ->setContractNo($inputType->getContractNo())
            ->setCanSellFood($inputType->isCanSellFood())
            ->setCanSellNonFood($inputType->isCanSellNonFood())
            ->setCanSellCashback($inputType->isCanSellCashback())
            ->setCanDoRemoteDistributions($inputType->getCanDoRemoteDistributions());

        $this->vendorRepository->save($vendor);

        return $vendor;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function update(Vendor $vendor, VendorUpdateInputType $inputType): Vendor
    {
        $location = $this->locationRepository->find($inputType->getLocationId());

        if (!$location instanceof Location) {
            throw new EntityNotFoundException('Location with ID #' . $inputType->getLocationId() . ' does not exists.');
        }

        $vendor->setShop($inputType->getShop())
            ->setName($inputType->getName())
            ->setAddressStreet($inputType->getAddressStreet())
            ->setAddressNumber($inputType->getAddressNumber())
            ->setAddressPostcode($inputType->getAddressPostcode())
            ->setLocation($location)
            ->setVendorNo($inputType->getVendorNo())
            ->setContractNo($inputType->getContractNo())
            ->setCanSellFood($inputType->isCanSellFood())
            ->setCanSellNonFood($inputType->isCanSellNonFood())
            ->setCanSellCashback($inputType->isCanSellCashback())
            ->setCanDoRemoteDistributions($inputType->getCanDoRemoteDistributions());

        $this->vendorRepository->save($vendor);

        return $vendor;
    }

    /**
     * Archives Vendor
     *
     * @return Vendor
     * @throws Exception
     */
    public function archiveVendor(Vendor $vendor, bool $archiveVendor = true): Vendor
    {
        try {
            $vendor->setArchived($archiveVendor);
            $this->vendorRepository->save($vendor);
        } catch (Exception) {
            throw new Exception('Error archiving Vendor');
        }

        return $vendor;
    }

    /**
     * @throws NotFoundHttpException
     */
    public function getVendorByUser(User $user): Vendor
    {
        $vendor = $this->vendorRepository->findOneByUser($user);
        if (!$vendor) {
            throw new NotFoundHttpException(
                "Vendor bind to user (Username: {$user->getUsername()}, ID: {$user->getId()}) does not exists."
            );
        }

        return $vendor;
    }

    public function printInvoice(Vendor $vendor): BinaryFileResponse
    {
        $voucherPurchases = $this->voucherPurchaseRepository->findByVendor($vendor);
        if (0 === (is_countable($voucherPurchases) ? count($voucherPurchases) : 0)) {
            throw new Exception('This vendor has no voucher. Try syncing with the server.');
        }
        $totalValue = 0;
        foreach ($voucherPurchases as $voucherPurchase) {
            foreach ($voucherPurchase->getRecords() as $record) {
                $totalValue += $record->getValue();
            }
        }

        $location = $vendor->getLocation();
        $locationCountry = $location?->getCountryIso3();
        $locationNames = [
            'adm1' => null,
            'adm2' => null,
            'adm3' => null,
            'adm4' => null,
        ];

        while ($location !== null) {
            $locationNames['adm' . $location->getLvl()] = $location->getName();
            $location = $location->getParent();
        }

        $html = $this->twig->render(
            'Pdf/invoice.html.twig',
            array_merge(
                [
                    'name' => $vendor->getName(),
                    'shop' => $vendor->getShop(),
                    'addressStreet' => $vendor->getAddressStreet(),
                    'addressPostcode' => $vendor->getAddressPostcode(),
                    'addressNumber' => $vendor->getAddressNumber(),
                    'vendorNo' => $vendor->getVendorNo(),
                    'contractNo' => $vendor->getContractNo(),
                    'addressVillage' => $locationNames['adm4'],
                    'addressCommune' => $locationNames['adm3'],
                    'addressDistrict' => $locationNames['adm2'],
                    'addressProvince' => $locationNames['adm1'],
                    'addressCountry' => $locationCountry,
                    'date' => (new DateTime())->format('d-m-Y'),
                    'voucherPurchases' => $voucherPurchases,
                    'totalValue' => $totalValue,
                ],
                $this->pdfService->getInformationStyle()
            )
        );

        return $this->pdfService->printPdf($html, 'portrait', 'invoice');
    }

    /**
     * Export all vendors in a CSV file
     *
     * @return string
     * @throws ExportNoDataException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportToCsv(string $type, string $countryISO3): string
    {
        $exportableTable = $this->vendorRepository->findByCountry($countryISO3);

        return $this->exportService->export($exportableTable, 'vendors', $type);
    }
}
