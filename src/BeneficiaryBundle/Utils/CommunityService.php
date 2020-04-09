<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Camp;
use BeneficiaryBundle\Entity\CampAddress;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\CommunityLocation;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Form\CommunityConstraints;
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
 * Class CommunityService
 * @package BeneficiaryBundle\Utils
 */
class CommunityService
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
     * CommunityService constructor.
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

        $communitys = $this->em->getRepository(Community::class)->getAllBy($iso3, $limitMinimum, $pageSize, $sort);
        $length = $communitys[0];
        $communitys = $communitys[1];

        return [$length, $communitys];
    }

    public function create(string $iso3, array $communityArray): Community
    {
        $this->requestValidator->validate(
            "community",
            CommunityConstraints::class,
            $communityArray,
            'any'
        );

        $community = new Community();
        $community->setLongitude($communityArray['longitude'] ?? null);
        $community->setLatitude($communityArray['latitude'] ?? null);
        $community->setIdNumber($communityArray['id_number'] ?? null);
        $community->setIdType($communityArray['id_type'] ?? null);
        $community->setContactName($communityArray['contact_name'] ?? null);
        $community->setPhonePrefix($communityArray['phone_prefix'] ?? null);
        $community->setPhoneNumber($communityArray['phone_number'] ?? null);

        if (isset($communityArray['address'])) {
            $this->requestValidator->validate(
                "address",
                CommunityConstraints::class,
                $communityArray['address'],
                'any'
            );

            $location = $this->locationService->getLocation($iso3, $communityArray['address']["location"]);

            $community->setAddress(Address::create(
                $communityArray['address']['street'],
                $communityArray['address']['number'],
                $communityArray['address']['postcode'],
                $location
                ));
        }

        return $community;
    }

    public function remove(Community $community)
    {
        $community->setArchived(true);
        $this->em->persist($community);
        $this->em->flush();

        return $community;
    }

    public function removeMany(array $communityIds)
    {
        foreach ($communityIds as $communityId) {
            $community = $this->em->getRepository(Community::class)->find($communityId);
            $community->setArchived(true);
            $this->em->persist($community);
        }
        $this->em->flush();
        return "Communitys have been archived";
    }

    public function update($iso3, Community $community, $communityArray): Community
    {
        if (array_key_exists('longitude', $communityArray)) {
            $community->setLongitude($communityArray['longitude']);
        }
        if (array_key_exists('latitude', $communityArray)) {
            $community->setLatitude($communityArray['latitude']);
        }
        if (array_key_exists('id_number', $communityArray)) {
            $community->setIdNumber($communityArray['id_number']);
        }
        if (array_key_exists('id_type', $communityArray)) {
            $community->setIdType($communityArray['id_type']);
        }
        if (array_key_exists('contact_name', $communityArray)) {
            $community->setContactName($communityArray['contact_name'] ?? null);
        }
        if (array_key_exists('phone_prefix', $communityArray)) {
            $community->setPhonePrefix($communityArray['phone_prefix']);
        }
        if (array_key_exists('phone_number', $communityArray)) {
            $community->setPhoneNumber($communityArray['phone_number']);
        }

        if (array_key_exists('address', $communityArray)) {
            $location = null;
            if (array_key_exists('location', $communityArray['address'])) {
                $location = $this->locationService->getLocation($iso3, $communityArray['address']['location']);
            }
            $this->updateAddress($community, Address::create(
                $communityArray['address']['street'],
                $communityArray['address']['number'],
                $communityArray['address']['postcode'],
                $location
                ));
        }

        return $community;
    }

    private function updateAddress(Community $community, Address $newAddress)
    {
        if (null === $community->getAddress()) {
            $community->setAddress($newAddress);
            return;
        }
        if (! $community->getAddress()->equals($newAddress)) {
            $this->em->remove($community->getAddress());
            $community->setAddress($newAddress);
        }
    }
}
