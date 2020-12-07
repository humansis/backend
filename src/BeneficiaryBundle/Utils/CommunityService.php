<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\InputType;
use CommonBundle\InputType as GeneralInputType;
use CommonBundle\InputType\DataTableType;
use CommonBundle\Mapper\LocationMapper;
use CommonBundle\Utils\LocationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\RequestValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class CommunityService.
 */
class CommunityService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var Serializer */
    private $serializer;

    /** @var BeneficiaryService */
    private $beneficiaryService;

    /** @var RequestValidator */
    private $requestValidator;

    /** @var LocationService */
    private $locationService;

    /** @var ValidatorInterface */
    private $validator;

    /** @var LocationMapper */
    private $locationMapper;

    /** @var ContainerInterface */
    private $container;

    /**
     * CommunityService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Serializer             $serializer
     * @param BeneficiaryService     $beneficiaryService
     * @param RequestValidator       $requestValidator
     * @param LocationService        $locationService
     * @param ValidatorInterface     $validator
     * @param ContainerInterface     $container
     * @param LocationMapper         $locationMapper
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        Serializer $serializer,
        BeneficiaryService $beneficiaryService,
        RequestValidator $requestValidator,
        LocationService $locationService,
        ValidatorInterface $validator,
        ContainerInterface $container,
        LocationMapper $locationMapper
    ) {
        $this->em = $entityManager;
        $this->serializer = $serializer;
        $this->beneficiaryService = $beneficiaryService;
        $this->requestValidator = $requestValidator;
        $this->locationService = $locationService;
        $this->validator = $validator;
        $this->container = $container;
        $this->locationMapper = $locationMapper;
    }

    /**
     * @param GeneralInputType\Country $country
     * @param DataTableType            $filters
     *
     * @return mixed
     */
    public function getAll(GeneralInputType\Country $country, DataTableType $filters)
    {
        $communities = $this->em->getRepository(Community::class)->getAllBy(
            $country->getIso3(),
            $filters->getLimitMinimum(),
            $filters->getPageSize(),
            $filters->getSort(),
            $filters->getFilter()
        );
        $length = $communities[0];
        $communities = $communities[1];

        return [$length, $communities];
    }

    /**
     * @param GeneralInputType\Country   $country
     * @param InputType\NewCommunityType $communityType
     *
     * @return Community
     *
     * @throws InvalidArgumentException
     */
    public function create(GeneralInputType\Country $country, InputType\NewCommunityType $communityType): Community
    {
        $community = new Community();
        $community->setLongitude($communityType->getLongitude() ?? '');
        $community->setLatitude($communityType->getLatitude() ?? '');
        $community->setContactName($communityType->getContactName() ?? '');
        $community->setContactFamilyName($communityType->getContactFamilyName() ?? '');
        if ($communityType->getPhoneNumber()) {
            $community->setPhone(new Phone());
            $community->getPhone()->setType($communityType->getPhoneType());
            $community->getPhone()->setPrefix($communityType->getPhonePrefix() ?? '');
            $community->getPhone()->setNumber($communityType->getPhoneNumber());
        }

        if (null !== $communityType->getNationalId() && !$communityType->getNationalId()->isEmpty()) {
            $community->setNationalId(new NationalId());
            $community->getNationalId()->setIdNumber($communityType->getNationalId()->getNumber());
            $community->getNationalId()->setIdType($communityType->getNationalId()->getType());
        }

        if (null !== $communityType->getAddress()) {
            $addressType = $communityType->getAddress();
            $location = $this->locationService->getLocationByInputType($country, $addressType->getLocation());

            $community->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
            ));
        }

        if ($community->getAddress() && $community->getAddress()->getLocation()) {
            $community->setName($this->locationMapper->toName($community->getAddress()->getLocation()));
        } else {
            $community->setName('global community');
        }

        foreach ($communityType->getProjects() as $projectId) {
            $project = $this->em->getRepository(Project::class)->find((int) $projectId);
            if (null === $project) {
                throw new InvalidArgumentException("Project $projectId doesn't exist");
            }
            $community->addProject($project);
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

        return 'Communities have been archived';
    }

    /**
     * @param GeneralInputType\Country      $country
     * @param Community                     $community
     * @param InputType\UpdateCommunityType $communityType
     *
     * @return Community
     *
     * @throws InvalidArgumentException
     */
    public function update(GeneralInputType\Country $country, Community $community, InputType\UpdateCommunityType $communityType): Community
    {
        if (null !== $newValue = $communityType->getLongitude()) {
            $community->setLongitude($newValue);
        }
        if (null !== $newValue = $communityType->getLatitude()) {
            $community->setLatitude($newValue);
        }

        if (null !== $communityType->getNationalId()) {
            if (null == $community->getNationalId()) {
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
        if (null !== $communityType->getPhoneNumber()) {
            if (null === $community->getPhone()) {
                $community->setPhone(new Phone());
            }
            $community->getPhone()->setType($communityType->getPhoneType());
            $community->getPhone()->setPrefix($communityType->getPhonePrefix());
            $community->getPhone()->setNumber($communityType->getPhoneNumber());
        }

        /** @var InputType\BeneficiaryAddressType $address */
        if (null !== $address = $communityType->getAddress()) {
            $location = null;
            if (null !== $address->getLocation()) {
                $location = $this->locationService->getLocationByInputType($country, $address->getLocation());
            }

            $this->updateAddress($community, Address::create(
                $address->getStreet(),
                $address->getNumber(),
                $address->getPostcode(),
                $location
            ));
        }

        if ($community->getAddress() && $community->getAddress()->getLocation()) {
            $community->setName($this->locationMapper->toName($community->getAddress()->getLocation()));
        } else {
            $community->setName('global community');
        }

        $community->setProjects(new ArrayCollection());
        foreach ($communityType->getProjects() as $projectId) {
            $project = $this->em->getRepository(Project::class)->find($projectId);
            if (null === $project) {
                throw new InvalidArgumentException("Project $projectId doesn't exist");
            }
            $community->addProject($project);
        }

        return $community;
    }

    private function updateAddress(Community $community, Address $newAddress)
    {
        if (null === $community->getAddress()) {
            $community->setAddress($newAddress);

            return;
        }
        if (!$community->getAddress()->equals($newAddress)) {
            $this->em->remove($community->getAddress());
            $community->setAddress($newAddress);
        }
    }
}
