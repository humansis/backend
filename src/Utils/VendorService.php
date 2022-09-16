<?php

namespace Utils;

use Entity\Location;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Exception\NotUniqueException;
use InputType\VendorCreateInputType;
use InputType\VendorUpdateInputType;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Entity\User;
use Entity\Vendor;
use Entity\VoucherPurchase;

class VendorService
{

  /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * UserService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface     $container
     * @param Environment            $twig
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        Environment $twig
    ) {
        $this->em = $entityManager;
        $this->container = $container;
        $this->twig = $twig;
    }


    /**
     * @param VendorCreateInputType $inputType
     * @return Vendor
     * @throws EntityNotFoundException
     * @throws NotUniqueException
     */
    public function create(VendorCreateInputType $inputType): Vendor
    {
        $user = $this->em->getRepository(User::class)->find($inputType->getUserId());

        if (!$user instanceof User) {
            throw new EntityNotFoundException('User with ID #'.$inputType->getUserId().' does not exists.');
        }

        $location = $this->em->getRepository(Location::class)->find($inputType->getLocationId());

        if (!$location instanceof Location) {
            throw new EntityNotFoundException('Location with ID #'.$inputType->getLocationId().' does not exists.');
        }

        if (null !== $user->getVendor()) {
            throw new \InvalidArgumentException('User with ID #'.$inputType->getUserId().' is already defined as vendor.');
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
            ->setCanDoRemoteDistributions($inputType->getCanDoRemoteDistributions())
        ;

        $this->em->persist($vendor);
        $this->em->flush();

        return $vendor;
    }

    /**
     * @param Vendor                $vendor
     * @param VendorUpdateInputType $inputType
     * @return Vendor
     * @throws EntityNotFoundException
     * @throws NotUniqueException
     */
    public function update(Vendor $vendor, VendorUpdateInputType $inputType): Vendor
    {
        $location = $this->em->getRepository(Location::class)->find($inputType->getLocationId());

        if (!$location instanceof Location) {
            throw new EntityNotFoundException('Location with ID #'.$inputType->getLocationId().' does not exists.');
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
            ->setCanDoRemoteDistributions($inputType->getCanDoRemoteDistributions())
        ;

        $this->em->persist($vendor);
        $this->em->flush();

        return $vendor;
    }

    /**
     * Archives Vendor
     *
     * @param Vendor $vendor
     * @param bool $archiveVendor
     * @return Vendor
     * @throws \Exception
     */
    public function archiveVendor(Vendor $vendor, bool $archiveVendor = true)
    {
        try {
            $vendor->setArchived($archiveVendor);
            $this->em->persist($vendor);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Error archiving Vendor');
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
        $vendor = $this->em->getRepository(Vendor::class)->findOneByUser($user);
        if (!$vendor) {
            throw new NotFoundHttpException("Vendor bind to user (Username: {$user->getUsername()}, ID: {$user->getId()}) does not exists.");
        }
        return $vendor;
    }

    public function printInvoice(Vendor $vendor)
    {
        try {
            $voucherPurchases = $this->em->getRepository(VoucherPurchase::class)->findByVendor($vendor);
            if (0 === count($voucherPurchases)) {
                throw new \Exception('This vendor has no voucher. Try syncing with the server.');
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
                    array(
                        'name'  => $vendor->getName(),
                        'shop'  => $vendor->getShop(),
                        'addressStreet'  => $vendor->getAddressStreet(),
                        'addressPostcode'  => $vendor->getAddressPostcode(),
                        'addressNumber'  => $vendor->getAddressNumber(),
                        'vendorNo' => $vendor->getVendorNo(),
                        'contractNo' => $vendor->getContractNo(),
                        'addressVillage' => $locationNames['adm4'],
                        'addressCommune' => $locationNames['adm3'],
                        'addressDistrict' => $locationNames['adm2'],
                        'addressProvince' => $locationNames['adm1'],
                        'addressCountry' => $locationCountry,
                        'date'  => (new DateTime())->format('d-m-Y'),
                        'voucherPurchases' => $voucherPurchases,
                        'totalValue' => $totalValue
                    ),
                    $this->container->get('pdf_service')->getInformationStyle()
                )
            );

            $response = $this->container->get('pdf_service')->printPdf($html, 'portrait', 'invoice');
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }

        return new Response('');
    }

    /**
     * Export all vendors in a CSV file
     * @param string $type
     * @param string $countryISO3
     * @return mixed
     */
    public function exportToCsv(string $type, string $countryISO3)
    {
        $exportableTable = $this->em->getRepository(Vendor::class)->findByCountry($countryISO3);

        return $this->container->get('export_csv_service')->export($exportableTable, 'vendors', $type);
    }
}
