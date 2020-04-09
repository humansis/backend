<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\CampAddress;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\InstitutionLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\InstitutionConstraints;
use CommonBundle\Entity\Location;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @param string $iso3
     * @param array $filters
     * @return mixed
     */
    public function getAll(string $iso3, array $filters)
    {
        $pageIndex = $filters['pageIndex'];
        $pageSize = $filters['pageSize'];
        $filter = $filters['filter'];
        $sort = $filters['sort'];

        $limitMinimum = $pageIndex * $pageSize;

        $institutions = $this->em->getRepository(Institution::class)->getAllBy($iso3, $limitMinimum, $pageSize, $sort);
        $length = $institutions[0];
        $institutions = $institutions[1];

        return [$length, $institutions];
    }

    public function create(string $iso3, array $institutionArray): Institution
    {
//        $this->requestValidator->validate(
//            "institution",
//            InstitutionConstraints::class,
//            $institutionArray,
//            'any'
//        );

        $institution = new Institution();
        $institution->setType($institutionArray['type']);
        $institution->setLongitude($institutionArray['longitude'] ?? null);
        $institution->setLatitude($institutionArray['latitude'] ?? null);
        $institution->setType($institutionArray['type'] ?? null);
        $institution->setIdNumber($institutionArray['id_number'] ?? null);
        $institution->setIdType($institutionArray['id_type'] ?? null);
        $institution->setContactName($institutionArray['contact_name'] ?? null);
        $institution->setPhonePrefix($institutionArray['phone_prefix'] ?? null);
        $institution->setPhoneNumber($institutionArray['phone_number'] ?? null);

        if (isset($institutionArray['address'])) {
            $location = $this->locationService->getLocation($iso3, $institutionArray['address']["location"]);

            $institution->setAddress(Address::create(
                $institutionArray['address']['street'],
                $institutionArray['address']['number'],
                $institutionArray['address']['postcode'],
                $location,
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
                $location,
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
