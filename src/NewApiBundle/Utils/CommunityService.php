<?php

namespace NewApiBundle\Utils;

use NewApiBundle\Entity\Address;
use NewApiBundle\Entity\Community;
use NewApiBundle\Entity\NationalId;
use NewApiBundle\Entity\Phone;
use NewApiBundle\InputType\Deprecated;
use NewApiBundle\Entity\Location;
use NewApiBundle\InputType as GeneralInputType;
use NewApiBundle\MapperDeprecated\LocationMapper;
use NewApiBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use InvalidArgumentException;
use NewApiBundle\InputType\CommunityCreateInputType;
use NewApiBundle\InputType\CommunityUpdateInputType;
use NewApiBundle\Entity\Project;


/**
 * Class CommunityService.
 */
class CommunityService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var LocationService */
    private $locationService;

    /** @var LocationMapper */
    private $locationMapper;

    /**
     * CommunityService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param LocationService        $locationService
     * @param LocationMapper         $locationMapper
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LocationService $locationService,
        LocationMapper $locationMapper
    ) {
        $this->em = $entityManager;
        $this->locationService = $locationService;
        $this->locationMapper = $locationMapper;
    }

    /**
     * @param GeneralInputType\Country   $country
     * @param Deprecated\NewCommunityType $communityType
     *
     * @return Community
     *
     * @throws InvalidArgumentException
     *@deprecated Since added method createCommunity TODO Remove after migrate new application
     *
     */
    public function createDeprecated(GeneralInputType\Country $country, Deprecated\NewCommunityType $communityType): Community
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
            $location = $this->locationService->getLocationByInputType($addressType->getLocation());

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
