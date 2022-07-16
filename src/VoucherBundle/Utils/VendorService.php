<?php

namespace VoucherBundle\Utils;

use CommonBundle\Entity\Location;
use CommonBundle\Entity\Logs;
use CommonBundle\Utils\LocationService;
use Couchbase\DocumentNotFoundException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Dompdf\Dompdf;
use Dompdf\Options;
use NewApiBundle\Exception\NotUniqueException;
use NewApiBundle\InputType\VendorCreateInputType;
use NewApiBundle\InputType\VendorUpdateInputType;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;
use NewApiBundle\Entity\User;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Entity\VoucherPurchase;

class VendorService
{

  /** @var EntityManagerInterface $em */
    private $em;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;

    /** @var LocationService $locationService */
    private $locationService;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * UserService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface     $validator
     * @param LocationService        $locationService
     * @param ContainerInterface     $container
     * @param Environment            $twig
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LocationService $locationService,
        ContainerInterface $container,
        Environment $twig
    ) {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
        $this->locationService = $locationService;
        $this->twig = $twig;
    }

    /**
     * Creates a new Vendor entity
     *
     * @param array $vendorData
     * @return mixed
     * @throws \Exception
     */
    public function createFromArray($countryISO3, array $vendorData)
    {
        $username = $vendorData['username'];
        $userSaved = $this->em->getRepository(User::class)->findOneByUsername($username);
        $vendorSaved = $userSaved instanceof User ? $this->em->getRepository(Vendor::class)->getVendorByUser($userSaved) : null;

        if (!($vendorSaved instanceof Vendor)) {
            $user = $this->container->get('user.user_service')->createFromArray(
                [
                    'username' => $username,
                    'email' => $username,
                    'roles' => ['ROLE_VENDOR'],
                    'password' => $vendorData['password'],
                    'salt' => $vendorData['salt'],
                    'change_password' => false,
                    'phone_prefix' => '+34',
                    'phone_number' => '675676767',
                    'two_factor_authentication' => false
                ]
            );

            $location = $vendorData['location'];
            $location = $this->locationService->getLocation($countryISO3, $location);

            $vendor = new Vendor();
            $vendor->setName($vendorData['name'])
                    ->setShop($vendorData['shop'])
                    ->setAddressStreet($vendorData['address_street'])
                    ->setAddressNumber($vendorData['address_number'])
                    ->setAddressPostcode($vendorData['address_postcode'])
                    ->setLocation($location)
                    ->setArchived(false)
                    ->setUser($user);

            $this->em->persist($vendor);
            $this->em->flush();

            $createdVendor = $this->em->getRepository(Vendor::class)->findOneByUser($user);
            return $createdVendor;
        } else {
            throw new \Exception('A vendor with this username already exists.');
        }
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
     * Returns all the vendors
     *
     * @return array
     */
    public function findAll($countryISO3)
    {
        $vendors = $this->em->getRepository(Vendor::class)->findByCountry($countryISO3);
        return $vendors;
    }


    /**
     * Updates a vendor according to $vendorData
     *
     * @param Vendor $vendor
     * @param array $vendorData
     * @return Vendor
     */
    public function updateFromArray($countryISO3, Vendor $vendor, array $vendorData)
    {
        try {
            $user = $vendor->getUser();
            foreach ($vendorData as $key => $value) {
                if ($key == 'name') {
                    $vendor->setName($value);
                } elseif ($key == 'shop') {
                    $vendor->setShop($value);
                } elseif ($key == 'address_street') {
                    $vendor->setAddressStreet($vendorData['address_street']);
                } elseif ($key == 'address_number') {
                    $vendor->setAddressNumber($vendorData['address_number']);
                } elseif ($key == 'address_postcode') {
                    $vendor->setAddressPostcode($vendorData['address_postcode']);
                } elseif ($key == 'username') {
                    $user->setUsername($value);
                } elseif ($key == 'password' && !empty($value)) {
                    $user->setPassword($value);
                } elseif ($key == 'location' && !empty($value)) {
                    $location = $value;
                    if (array_key_exists('id', $location)) {
                        unset($location['id']); // This is the old id
                    }
                    $location = $this->locationService->getLocation($countryISO3, $location);
                    $vendor->setLocation($location);
                }
            }
            $this->em->persist($vendor);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception('Error updating Vendor');
        }

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
     * Permanently deletes the record from the database
     *
     * @param Vendor $vendor
     * @param bool $removeVendor
     * @return bool
     */
    public function deleteFromDatabase(Vendor $vendor, bool $removeVendor = true)
    {
        if ($removeVendor) {
            try {
                $this->em->remove($vendor);
                $this->em->flush();
            } catch (\Exception $exception) {
                return $exception;
            }
        }
        return true;
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
            $locationCountry = $location ? $location->getCountryISO3() : null; 
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
