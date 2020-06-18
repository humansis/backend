<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\InputType;
use CommonBundle\InputType as GeneralInputType;
use BeneficiaryBundle\Form\CommunityConstraints;
use CommonBundle\InputType\DataTableType;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
     * @param GeneralInputType\Country $country
     * @param DataTableType $filters
     * @return mixed
     */
    public function getAll(GeneralInputType\Country $country, DataTableType $filters)
    {
        $communities = $this->em->getRepository(Community::class)->getAllBy(
            $country->getIso3(),
            $filters->getLimitMinimum(),
            $filters->getPageSize(),
            $filters->getFilter(),
            $filters->getSort()
        );
        $length = $communities[0];
        $communities = $communities[1];

        return [$length, $communities];
    }

    public function create(GeneralInputType\Country $country, InputType\UpdateCommunityType $communityType): Community
    {
        $community = new Community();
        $community->setLongitude($communityType->getLongitude() ?? '');
        $community->setLatitude($communityType->getLatitude() ?? '');
        $community->setContactName($communityType->getContactName() ?? '');
        $community->setContactFamilyName($communityType->getContactFamilyName() ?? '');
        $community->setPhonePrefix($communityType->getPhonePrefix() ?? '');
        $community->setPhoneNumber($communityType->getPhoneNumber() ?? '');

        if ($communityType->getNationalId() !== null) {
            $community->setNationalId(new NationalId());
            $community->getNationalId()->setIdNumber($communityType->getNationalId()->getNumber());
            $community->getNationalId()->setIdType($communityType->getNationalId()->getType());
        }

        if ($communityType->getAddress() !== null) {
            $addressType = $communityType->getAddress();
            $location = $this->locationService->getLocationByInputType($country, $addressType->getLocation());

            $community->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
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

    public function update(GeneralInputType\Country $country, Community $community, InputType\UpdateCommunityType $communityType): Community
    {
        if (null !== $newValue = $communityType->getLongitude()) {
            $community->setLongitude($newValue);
        }
        if (null !== $newValue = $communityType->getLatitude()) {
            $community->setLatitude($newValue);
        }

        if ($communityType->getNationalId() !== null) {
            if ($community->getNationalId() == null) {
                $community->setNationalId(new NationalId());
            }
            $community->getNationalId()->setIdType($communityType->getNationalId()->getType());
            $community->getNationalId()->setIdNumber($communityType->getNationalId()->getNumber());
        }
        if (null !== $newValue = $communityType->getContactName()) {
            $community->setContactName($newValue);
        }
        if (null !== $newValue = $communityType->getContactFamilyName()) {
            $community->setContactFamilyName($newValue);
        }
        if (null !== $newValue = $communityType->getPhonePrefix()) {
            $community->setPhonePrefix($newValue);
        }
        if (null !== $newValue = $communityType->getPhoneNumber()) {
            $community->setPhoneNumber($newValue);
        }

        /** @var InputType\BeneficiaryAddressType $address */
        if (null !== $address = $communityType->getAddress()) {
            $location = null;
            if ($address->getLocation() !== null) {
                $location = $this->locationService->getLocationByInputType($country, $address->getLocation());
            }
            $this->updateAddress($community, Address::create(
                $address->getStreet(),
                $address->getNumber(),
                $address->getPostcode(),
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
