<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Form\InstitutionConstraints;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use CommonBundle\InputType as GlobalInputType;
use BeneficiaryBundle\InputType;

/**
 * Class InstitutionService
 * @package BeneficiaryBundle\Utils
 */
class InstitutionService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var RequestValidator $requestValidator */
    private $requestValidator;

    /** @var LocationService $locationService */
    private $locationService;

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var ContainerInterface $container */
    private $container;


    /**
     * InstitutionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param Serializer $serializer
     * @param BeneficiaryService $beneficiaryService
     * @param RequestValidator $requestValidator
     * @param LocationService $locationService
     * @param ValidatorInterface $validator
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        BeneficiaryService $beneficiaryService,
        RequestValidator $requestValidator,
        LocationService $locationService,
        ValidatorInterface $validator,
        ContainerInterface $container
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
        $this->locationService = $locationService;
        $this->validator = $validator;
        $this->container= $container;
    }

    /**
     * @param GlobalInputType\Country $country
     * @param GlobalInputType\DataTableType $dataTableType
     * @return mixed
     */
    public function getAll(GlobalInputType\Country $country, GlobalInputType\DataTableType $dataTableType)
    {
        $limitMinimum = $dataTableType->pageIndex * $dataTableType->pageSize;

        $institutions = $this->em->getRepository(Institution::class)->getAllBy($country, $limitMinimum, $dataTableType->pageSize, $dataTableType->getSort());
        $length = $institutions[0];
        $institutions = $institutions[1];

        return [$length, $institutions];
    }

    /**
     * @param GlobalInputType\Country $country
     * @param InputType\NewInstitutionType $institutionType
     * @return Institution
     */
    public function create(GlobalInputType\Country $country, InputType\NewInstitutionType $institutionType): Institution
    {
        $institution = new Institution();
        $institution->setType($institutionType->getType());
        $institution->setLongitude($institutionType->getLongitude());
        $institution->setLatitude($institutionType->getLatitude());
        $institution->setIdNumber($institutionType->getIdNumber());
        $institution->setIdType($institutionType->getIdType());
        $institution->setContactName($institutionType->getContactName());
        $institution->setContactFamilyName($institutionType->getContactFamilyName());
        $institution->setPhonePrefix($institutionType->getPhonePrefix());
        $institution->setPhoneNumber($institutionType->getPhoneNumber());

        if ($institutionType->getAddress() !== null) {
            $addressType = $institutionType->getAddress();
            $location = $this->locationService->getLocationByInputType($country, $addressType->getLocation());

            $institution->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
                ));
        }

        return $institution;
    }

    public function remove(Institution $institution)
    {
        $institution->setArchived(true);
        $this->em->persist($institution);
        $this->em->flush();

        return $institution;
    }

    public function removeMany(array $institutionIds)
    {
        foreach ($institutionIds as $institutionId) {
            $institution = $this->em->getRepository(Institution::class)->find($institutionId);
            $institution->setArchived(true);
            $this->em->persist($institution);
        }
        $this->em->flush();
        return "Institutions have been archived";
    }


    /**
     * @return mixed
     */
    public function exportToCsv()
    {
        $exportableTable = $this->em->getRepository(Institution::class)->findAll();
        return  $this->container->get('export_csv_service')->export($exportableTable);
    }

    /**
     * @param array $institutionsArray
     * @return array
     */
    public function getAllImported(array $institutionsArray)
    {
        $institutionsId = $institutionsArray['institutions'];

        $institutions = array();

        foreach ($institutionsId as $institutionId) {
            $institution = $this->em->getRepository(Institution::class)->find($institutionId);

            if ($institution instanceof Institution) {
                array_push($institutions, $institution);
            }
        }

        return $institutions;
    }

    public function update($iso3, Institution $institution, $institutionArray): Institution
    {
        if (array_key_exists('longitude', $institutionArray)) {
            $institution->setLongitude($institutionArray['longitude']);
        }
        if (array_key_exists('latitude', $institutionArray)) {
            $institution->setLatitude($institutionArray['latitude']);
        }
        if (array_key_exists('type', $institutionArray)) {
            $institution->setType($institutionArray['type']);
        }
        if (array_key_exists('id_number', $institutionArray)) {
            $institution->setIdNumber($institutionArray['id_number']);
        }
        if (array_key_exists('id_type', $institutionArray)) {
            $institution->setIdType($institutionArray['id_type']);
        }
        if (array_key_exists('contact_name', $institutionArray)) {
            $institution->setContactName($institutionArray['contact_name'] ?? null);
        }
        if (array_key_exists('phone_prefix', $institutionArray)) {
            $institution->setPhonePrefix($institutionArray['phone_prefix']);
        }
        if (array_key_exists('phone_number', $institutionArray)) {
            $institution->setPhoneNumber($institutionArray['phone_number']);
        }

        if (array_key_exists('address', $institutionArray)) {
            $location = null;
            if (array_key_exists('location', $institutionArray['address'])) {
                $location = $this->locationService->getLocation($iso3, $institutionArray['address']['location']);
            }
            $this->updateAddress($institution, Address::create(
                $institutionArray['address']['street'],
                $institutionArray['address']['number'],
                $institutionArray['address']['postcode'],
                $location
                ));
        }

        return $institution;
    }

    private function updateAddress(Institution $institution, Address $newAddress)
    {
        if (null === $institution->getAddress()) {
            $institution->setAddress($newAddress);
            return;
        }
        if (! $institution->getAddress()->equals($newAddress)) {
            $this->em->remove($institution->getAddress());
            $institution->setAddress($newAddress);
        }
    }
}
