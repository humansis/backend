<?php

namespace Utils;

use Entity\Address;
use Entity\Institution;
use Entity\NationalId;
use Entity\Phone;
use Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Enum\EnumValueNoFoundException;
use InputType\InstitutionCreateInputType;
use InputType\InstitutionUpdateInputType;
use Entity\Project;

/**
 * Class InstitutionService
 *
 * @package Utils
 */
class InstitutionService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * InstitutionService constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->em = $entityManager;
    }

    /**
     * @param InstitutionCreateInputType $inputType
     *
     * @return Institution
     * @throws EntityNotFoundException
     * @throws EnumValueNoFoundException
     */
    public function create(InstitutionCreateInputType $inputType): Institution
    {
        $institution = new Institution();
        $institution->setName($inputType->getName());
        $institution->setType($inputType->getType());
        $institution->setLongitude($inputType->getLongitude());
        $institution->setLatitude($inputType->getLatitude());
        $institution->setContactName($inputType->getContactGivenName());
        $institution->setContactFamilyName($inputType->getContactFamilyName());

        foreach ($inputType->getProjectIds() as $id) {
            $project = $this->em->getRepository(Project::class)->find($id);
            if (!$project) {
                throw new EntityNotFoundException('project', $id);
            }

            $institution->addProject($project);
        }

        if ($inputType->getAddress()) {
            $addressType = $inputType->getAddress();

            /** @var Location|null $location */
            $location = $this->em->getRepository(Location::class)
                ->find($addressType->getLocationId());

            $institution->setAddress(
                Address::create(
                    $addressType->getStreet(),
                    $addressType->getNumber(),
                    $addressType->getPostcode(),
                    $location
                )
            );
        }

        if ($inputType->getPhone()) {
            $institution->setPhone(new Phone());
            $institution->getPhone()->setType($inputType->getPhone()->getType());
            $institution->getPhone()->setPrefix($inputType->getPhone()->getPrefix());
            $institution->getPhone()->setNumber($inputType->getPhone()->getNumber());
            $institution->getPhone()->setProxy($inputType->getPhone()->getProxy());
        }

        if ($inputType->getNationalIdCard()) {
            $institution->setNationalId(new NationalId());
            $institution->getNationalId()->setIdNumber($inputType->getNationalIdCard()->getNumber());
            $institution->getNationalId()->setIdType($inputType->getNationalIdCard()->getType());
        }

        $this->em->persist($institution);
        $this->em->flush();

        return $institution;
    }

    public function remove(Institution $institution)
    {
        $institution->setArchived(true);
        $this->em->persist($institution);
        $this->em->flush();
    }

    public function update(Institution $institution, InstitutionUpdateInputType $inputType): Institution
    {
        $institution->setName($inputType->getName());
        $institution->setType($inputType->getType());
        $institution->setLongitude($inputType->getLongitude());
        $institution->setLatitude($inputType->getLatitude());
        $institution->setContactName($inputType->getContactGivenName());
        $institution->setContactFamilyName($inputType->getContactFamilyName());

        $institution->getProjects()->clear();
        foreach ($inputType->getProjectIds() as $id) {
            $project = $this->em->getRepository(Project::class)->find($id);
            if (!$project) {
                throw new EntityNotFoundException('project', $id);
            }

            $institution->addProject($project);
        }

        $addressType = $inputType->getAddress();

        if (null === $addressType) {
            $institution->setAddress(null);
        } else {
            $institutionAddress = $institution->getAddress();
            if (null === $institution->getAddress()) {
                $institutionAddress = new Address();
                $institution->setAddress($institutionAddress);
            }

            /** @var Location|null $location */
            $location = $this->em->getRepository(Location::class)
                ->find($addressType->getLocationId());

            $institutionAddress->setLocation($location);
            $institutionAddress->setNumber($addressType->getNumber());
            $institutionAddress->setPostcode($addressType->getPostcode());
            $institutionAddress->setStreet($addressType->getStreet());
        }

        $nationalIdCardType = $inputType->getNationalIdCard();
        if (null === $nationalIdCardType) {
            $institution->setNationalId(null);
        } else {
            $institutionNationalIdCard = $institution->getNationalId();
            if (null === $institutionNationalIdCard) {
                $institutionNationalIdCard = new NationalId();
                $institution->setNationalId($institutionNationalIdCard);
            }

            $institutionNationalIdCard->setIdNumber($nationalIdCardType->getNumber());
            $institutionNationalIdCard->setIdType($nationalIdCardType->getType());
        }

        $phoneType = $inputType->getPhone();
        if (null === $phoneType) {
            $institution->setPhone(null);
        } else {
            $institutionPhone = $institution->getPhone();
            if (null === $institutionPhone) {
                $institutionPhone = new Phone();
                $institution->setPhone($institutionPhone);
            }

            $institutionPhone->setPrefix($phoneType->getPrefix());
            $institutionPhone->setNumber($phoneType->getNumber());
            $institutionPhone->setType($phoneType->getType());
            $institutionPhone->setProxy($phoneType->getProxy());
        }

        $this->em->flush();

        return $institution;
    }
}
