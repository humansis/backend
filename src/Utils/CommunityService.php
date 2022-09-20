<?php

namespace Utils;

use Entity\Address;
use Entity\Community;
use Entity\NationalId;
use Entity\Phone;
use Entity\Location;
use Enum\EnumValueNoFoundException;
use MapperDeprecated\LocationMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use InputType\CommunityCreateInputType;
use InputType\CommunityUpdateInputType;
use Entity\Project;


/**
 * Class CommunityService.
 */
class CommunityService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var LocationMapper */
    private $locationMapper;

    /**
     * CommunityService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param LocationMapper         $locationMapper
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LocationMapper $locationMapper
    ) {
        $this->em = $entityManager;
        $this->locationMapper = $locationMapper;
    }

    public function remove(Community $community)
    {
        $community->setArchived(true);
        $this->em->persist($community);
        $this->em->flush();
    }

    /**
     * @param CommunityCreateInputType $inputType
     *
     * @return Community
     * @throws EntityNotFoundException
     * @throws EnumValueNoFoundException
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
