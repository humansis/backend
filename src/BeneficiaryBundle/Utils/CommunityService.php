<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\InputType;
use CommonBundle\Entity\Location;
use CommonBundle\InputType as GeneralInputType;
use CommonBundle\InputType\DataTableType;
use CommonBundle\Mapper\LocationMapper;
use CommonBundle\Utils\LocationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use InvalidArgumentException;
use NewApiBundle\InputType\CommunityCreateInputType;
use NewApiBundle\InputType\CommunityUpdateInputType;
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
     * @deprecated Since added method createCommunity TODO Remove after migrate new application
     *
     * @param GeneralInputType\Country   $country
     * @param InputType\NewCommunityType $communityType
     *
     * @return Community
     *
     * @throws InvalidArgumentException
     */
    public function createDeprecated(GeneralInputType\Country $country, InputType\NewCommunityType $communityType): Community
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
            $community->getNationalId()->setIdNumber($communityType->getNationalId()->getPriority());
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
     *
     * @deprecated
     */
    public function updateDeprecated(GeneralInputType\Country $country, Community $community, InputType\UpdateCommunityType $communityType): Community
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
            $community->getNationalId()->setIdNumber($communityType->getNationalId()->getPriority());
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

    /**
     * @param CommunityCreateInputType $inputType
     *
     * @return Community
     */
    public function create(CommunityCreateInputType $inputType): Community
    {
        $community = new Community();
        $community->setLongitude($inputType->getLongitude());
        $community->setLatitude($inputType->getLongitude());
        $community->setContactFamilyName($inputType->getContactFamilyName());
        $community->setContactName($inputType->getContactGivenName());

        foreach ($inputType->getProjectIds() as $id) {
            $project = $this->em->getRepository(Project::class)->find($id);
            if (!$project) {
                throw new EntityNotFoundException('project', $id);
            }

            $community->addProject($project);
        }

        if (!is_null($inputType->getAddress())) {
            $addressType = $inputType->getAddress();

            /** @var Location|null $location */
            $location = $this->em->getRepository(Location::class)
                ->find($addressType->getLocationId());

            $community->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
            ));
        }

        if (!is_null($inputType->getNationalIdCard())) {
            $nationalIdCard = new NationalId();

            $nationalIdCard->setIdNumber($inputType->getNationalIdCard()->getNumber());
            $nationalIdCard->setIdType($inputType->getNationalIdCard()->getType());

            $community->setNationalId($nationalIdCard);
        }

        if (!is_null($inputType->getPhone())) {
            $phone = new Phone();

            $phone->setPrefix($inputType->getPhone()->getPrefix());
            $phone->setNumber($inputType->getPhone()->getNumber());
            $phone->setType($inputType->getPhone()->getType());
            $phone->setProxy($inputType->getPhone()->getProxy());

            $community->setPhone($phone);
        }

        if ($community->getAddress() && $community->getAddress()->getLocation()) {
            $community->setName($this->locationMapper->toName($community->getAddress()->getLocation()));
        } else {
            $community->setName('global community');
        }

        $this->em->persist($community);
        $this->em->flush();

        return $community;
    }

    public function update(Community $community, CommunityUpdateInputType $inputType)
    {
        $community->setLongitude($inputType->getLongitude());
        $community->setLatitude($inputType->getLatitude());
        $community->setContactName($inputType->getContactGivenName());
        $community->setContactFamilyName($inputType->getContactFamilyName());

        $community->getProjects()->clear();
        foreach ($inputType->getProjectIds() as $id) {
            $project = $this->em->getRepository(Project::class)->find($id);
            if (!$project) {
                throw new EntityNotFoundException('project', $id);
            }

            $community->addProject($project);
        }

        $addressType = $inputType->getAddress();

        if (null === $addressType) {
            $community->setAddress(null);
        } else {
            $communityAddress = $community->getAddress();
            if (null === $community->getAddress()) {
                $communityAddress = new Address();
                $community->setAddress($communityAddress);
            }

            /** @var Location|null $location */
            $location = $this->em->getRepository(Location::class)
                ->find($addressType->getLocationId());

            $communityAddress->setLocation($location);
            $communityAddress->setNumber($addressType->getNumber());
            $communityAddress->setPostcode($addressType->getPostcode());
            $communityAddress->setStreet($addressType->getStreet());
        }

        $nationalIdCardType = $inputType->getNationalIdCard();
        if (null === $nationalIdCardType) {
            $community->setNationalId(null);
        } else {
            $communityNationalIdCard = $community->getNationalId();
            if (null === $communityNationalIdCard) {
                $communityNationalIdCard = new NationalId();
                $community->setNationalId($communityNationalIdCard);
            }

            $communityNationalIdCard->setIdNumber($nationalIdCardType->getNumber());
            $communityNationalIdCard->setIdType($nationalIdCardType->getType());
        }

        $phoneType = $inputType->getPhone();
        if (null === $phoneType) {
            $community->setPhone(null);
        } else {
            $communityPhone = $community->getPhone();
            if (null === $communityPhone) {
                $communityPhone = new Phone();
                $community->setPhone($communityPhone);
            }

            $communityPhone->setPrefix($phoneType->getPrefix());
            $communityPhone->setNumber($phoneType->getNumber());
            $communityPhone->setType($phoneType->getType());
            $communityPhone->setProxy($phoneType->getProxy());
        }

        if ($community->getAddress() && $community->getAddress()->getLocation()) {
            $community->setName($this->locationMapper->toName($community->getAddress()->getLocation()));
        } else {
            $community->setName('global community');
        }

        $this->em->flush();

        return $community;
    }
}
