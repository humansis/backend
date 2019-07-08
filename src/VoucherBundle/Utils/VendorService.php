<?php

namespace VoucherBundle\Utils;

use CommonBundle\Entity\Logs;
use CommonBundle\Utils\LocationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use JMS\Serializer\Serializer;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Vendor;

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
     * UserService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     * @param LocationService $locationService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LocationService $locationService,
        ContainerInterface $container
    ) {
        $this->em = $entityManager;
        $this->validator = $validator;
        $this->container = $container;
        $this->locationService = $locationService;
    }

    /**
     * Creates a new Vendor entity
     *
     * @param array $vendorData
     * @return mixed
     * @throws \Exception
     */
    public function create($countryISO3, array $vendorData)
    {
        $username = $vendorData['username'];
        $userSaved = $this->em->getRepository(User::class)->findOneByUsername($username);
        $vendorSaved = $userSaved instanceof User ? $this->em->getRepository(Vendor::class)->getVendorByUser($userSaved) : null;

        if (!($vendorSaved instanceof Vendor)) {
            $user = $this->container->get('user.user_service')->create(
                [
                    'username' => $username,
                    'email' => $vendorData['email'],
                    'roles' => ['ROLE_VENDOR'],
                    'password' => $vendorData['password'],
                    'salt' => $vendorData['salt'],
                    'change_password' => false,
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
    public function update($countryISO3, Vendor $vendor, array $vendorData)
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
            $this->em->merge($vendor);
            $this->em->flush();
        } catch (\Exception $e) {
            throw new \Exception('Error updating Vendor');
        }

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
            $this->em->merge($vendor);
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
       * @throws \Exception
       */
    public function login(User $user)
    {
        $vendor = $this->em->getRepository(Vendor::class)->findOneByUser($user);
        if (!$vendor) {
            throw new \Exception('You cannot log if you are not a vendor', Response::HTTP_BAD_REQUEST);
        }

        return $vendor;
    }

    public function printInvoice(Vendor $vendor)
    {
        try {
            $now = new DateTime();
            $vouchers = $vendor->getVouchers();
            if (!count($vouchers)) {
                throw new \Exception('This vendor has no voucher. Try syncing with the server.');
            }
            $totalValue = 0;
            foreach ($vouchers as $voucher) {
                $voucher->setusedAt($voucher->getusedAt()->format('d-m-Y'));
                $totalValue += $voucher->getValue();
            }

            $location = $vendor->getLocation();

            if ($location && $location->getAdm4()) {
                $village = $location->getAdm4();
                $commune = $village->getAdm3();
                $district = $commune->getAdm2();
                $province = $district->getAdm1();
            } elseif ($location && $location->getAdm3()) {
                $commune = $location->getAdm3();
                $district = $commune->getAdm2();
                $province = $district->getAdm1();
                $village = null;
            } elseif ($location && $location->getAdm2()) {
                $district = $location->getAdm2();
                $province = $district->getAdm1();
                $village = null;
                $commune = null;
            } elseif ($location && $location->getAdm1()) {
                $province = $location->getAdm1();
                $village = null;
                $commune = null;
                $district = null;
            } else {
                $village = null;
                $commune = null;
                $district = null;
                $province = null;
            }

            $html = $this->container->get('templating')->render(
                '@Voucher/Pdf/invoice.html.twig',
                array_merge(
                    array(
                        'name'  => $vendor->getName(),
                        'shop'  => $vendor->getShop(),
                        'addressStreet'  => $vendor->getAddressStreet(),
                        'addressPostcode'  => $vendor->getAddressPostcode(),
                        'addressNumber'  => $vendor->getAddressNumber(),
                        'addressVillage' => $village ? $village->getName() : null,
                        'addressCommune' => $commune ? $commune->getName() : null,
                        'addressDistrict' => $district ? $district->getName() : null,
                        'addressProvince' => $province ? $province->getName() : null,
                        'addressCountry' => $province ? $province->getCountryISO3() : null,
                        'date'  => $now->format('d-m-Y'),
                        'vouchers' => $vouchers,
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
