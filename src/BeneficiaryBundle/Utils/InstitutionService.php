<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use CommonBundle\Entity\Location;
use CommonBundle\Utils\LocationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\InputType\InstitutionCreateInputType;
use NewApiBundle\InputType\InstitutionUpdateInputType;
use ProjectBundle\Entity\Project;
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

    /** @var LocationService $locationService */
    private $locationService;

    /**
     * InstitutionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param LocationService $locationService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LocationService $locationService
    ) {
        $this->em = $entityManager;
        $this->locationService = $locationService;
    }

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

            $institution->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
            ));
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

    /**
     * @param GlobalInputType\Country      $country
     * @param InputType\NewInstitutionType $institutionType
     *
     * @return Institution
     * @throws \InvalidArgumentException
     *
     * @deprecated
     */
    public function createDeprecated(GlobalInputType\Country $country, InputType\NewInstitutionType $institutionType): Institution
    {
        $institution = new Institution();
        $institution->setName($institutionType->getName());
        $institution->setType($institutionType->getType());
        $institution->setLongitude($institutionType->getLongitude());
        $institution->setLatitude($institutionType->getLatitude());
        $institution->setContactName($institutionType->getContactName());
        $institution->setContactFamilyName($institutionType->getContactFamilyName());
        if ($institutionType->getPhoneNumber()) {
            $institution->setPhone(new Phone());
            $institution->getPhone()->setType($institutionType->getPhoneType());
            $institution->getPhone()->setPrefix($institutionType->getPhonePrefix());
            $institution->getPhone()->setNumber($institutionType->getPhoneNumber());
        }


        if ($institutionType->getNationalId() !== null && !$institutionType->getNationalId()->isEmpty()) {
            $institution->setNationalId(new NationalId());
            $institution->getNationalId()->setIdNumber($institutionType->getNationalId()->getNumber());
            $institution->getNationalId()->setIdNumber($institutionType->getNationalId()->getNumber());
            $institution->getNationalId()->setIdType($institutionType->getNationalId()->getType());
        }

        if ($institutionType->getAddress() !== null) {
            $addressType = $institutionType->getAddress();
            $location = $this->locationService->getLocationByInputType($addressType->getLocation());

            $institution->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
                ));
        }

        foreach ($institutionType->getProjects() as $projectId) {
            $project = $this->em->getRepository(Project::class)->find($projectId);
            if (null === $project) {
                throw new \InvalidArgumentException("Project $projectId doesn't exist");
            }
            $institution->addProject($project);
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

    public function update(Institution $institution, InstitutionUpdateInputType $inputType)
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
