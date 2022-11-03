<?php

namespace Utils;

use Component\Smartcard\Invoice\PreliminaryInvoiceService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Location;
use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use Enum\EnumValueNoFoundException;
use Exception;
use Exception\ExportNoDataException;
use InputType\VendorCreateInputType;
use InputType\VendorFilterInputType;
use InputType\VendorOrderInputType;
use InputType\VendorUpdateInputType;
use InvalidArgumentException;
use Repository\LocationRepository;
use Repository\UserRepository;
use Repository\VendorRepository;
use Repository\VoucherPurchaseRepository;
use Request\Pagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Entity\User;
use Entity\Vendor;

class VendorService
{
    /** @var PdfService */
    private $pdfService;

    /**
     * @var Environment
     */
    private $twig;

    /** @var ExportService */
    private $exportService;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var LocationRepository
     */
    private $locationRepository;

    /**
     * @var VendorRepository
     */
    private $vendorRepository;

    /**
     * @var VoucherPurchaseRepository
     */
    private $voucherPurchaseRepository;

    /**
     * @var PreliminaryInvoiceService
     */
    private $preliminaryInvoiceService;

    /**
     * UserService constructor.
     *
     * @param PdfService $pdfService
     * @param Environment $twig
     * @param ExportService $exportService
     * @param UserRepository $userRepository
     * @param LocationRepository $locationRepository
     * @param VendorRepository $vendorRepository
     * @param VoucherPurchaseRepository $voucherPurchaseRepository
     * @param PreliminaryInvoiceService $preliminaryInvoiceService
     */
    public function __construct(
        PdfService $pdfService,
        Environment $twig,
        ExportService $exportService,
        UserRepository $userRepository,
        LocationRepository $locationRepository,
        VendorRepository $vendorRepository,
        VoucherPurchaseRepository $voucherPurchaseRepository,
        PreliminaryInvoiceService $preliminaryInvoiceService
    ) {
        $this->pdfService = $pdfService;
        $this->twig = $twig;
        $this->exportService = $exportService;
        $this->userRepository = $userRepository;
        $this->locationRepository = $locationRepository;
        $this->vendorRepository = $vendorRepository;
        $this->voucherPurchaseRepository = $voucherPurchaseRepository;
        $this->preliminaryInvoiceService = $preliminaryInvoiceService;
    }

    /**
     * @param string $countryIso3
     * @param VendorFilterInputType $vendorFilterInputType
     * @param VendorOrderInputType $vendorOrderInputType
     * @param Pagination|null $pagination
     * @return Paginator
     * @throws EnumValueNoFoundException
     */
    public function listVendors(
        string $countryIso3,
        VendorFilterInputType $vendorFilterInputType,
        VendorOrderInputType $vendorOrderInputType,
        ?Pagination $pagination = null
    ): Paginator {
        if ($vendorFilterInputType->hasInvoicing()) {
            $filteredVendors = $this->vendorRepository->findByParams(
                $countryIso3,
                $vendorFilterInputType,
                $vendorOrderInputType
            );
            $vendorsInInvoicingState = $this->preliminaryInvoiceService->filterVendorsByInvoicing(
                $filteredVendors->getQuery()->getResult(),
                $vendorFilterInputType->getInvoicing()
            );

            return $this->vendorRepository->getVendorsPaginatorByEntityRoot($vendorsInInvoicingState, $pagination);
        } else {
            return $this->vendorRepository->findByParams(
                $countryIso3,
                $vendorFilterInputType,
                $vendorOrderInputType,
                $pagination
            );
        }
    }

    /**
     * @param VendorCreateInputType $inputType
     * @return Vendor
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @param Vendor $vendor
     * @param VendorUpdateInputType $inputType
     * @return Vendor
     * @throws EntityNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @param Vendor $vendor
     * @param bool $archiveVendor
     * @return Vendor
     * @throws Exception
     */
    public function archiveVendor(Vendor $vendor, bool $archiveVendor = true): Vendor
    {
        try {
            $vendor->setArchived($archiveVendor);
            $this->vendorRepository->save($vendor);
        } catch (Exception $exception) {
            throw new Exception('Error archiving Vendor');
        }

        return $vendor;
    }

    /**
     * @param User $user
     * @return Vendor
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
        try {
            $voucherPurchases = $this->voucherPurchaseRepository->findByVendor($vendor);
            if (0 === count($voucherPurchases)) {
                throw new Exception('This vendor has no voucher. Try syncing with the server.');
            }
            $totalValue = 0;
            foreach ($voucherPurchases as $voucherPurchase) {
                foreach ($voucherPurchase->getRecords() as $record) {
                    $totalValue += $record->getValue();
                }
            }

            $location = $vendor->getLocation();
            $locationCountry = $location ? $location->getCountryIso3() : null;
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
                '@Voucher/Pdf/invoice.html.twig',
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

            $response = $this->pdfService->printPdf($html, 'portrait', 'invoice');

            return $response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Export all vendors in a CSV file
     *
     * @param string $type
     * @param string $countryISO3
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
