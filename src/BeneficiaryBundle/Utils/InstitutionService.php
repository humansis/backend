<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Form\InstitutionConstraints;
use CommonBundle\Entity\Location;
use CommonBundle\Utils\LocationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use NewApiBundle\Api\ReflexiveFiller;
use NewApiBundle\InputType\Beneficiary\AddressInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\InstitutionCreateInputType;
use NewApiBundle\InputType\InstitutionUpdateInputType;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\Serializer\SerializerInterface as Serializer;
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

        $institutions = $this->em->getRepository(Institution::class)->getAllBy(
            $country,
            $limitMinimum,
            $dataTableType->pageSize,
            $dataTableType->getSort(),
            $dataTableType->getFilter()
        );
        $length = $institutions[0];
        $institutions = $institutions[1];

        return [$length, $institutions];
    }

    public function create(InstitutionCreateInputType $inputType): Institution
    {
        $institution = new Institution();
        
        $filler = new ReflexiveFiller();
        // $filler->ignore(['projectIds']);
        $filler->map('projectIds', 'projects');
        $filler->map('contactGivenName', 'contactName');
        $filler->map('contactFamilyName', 'contactFamilyName');
        $filler->callback('address', function (AddressInputType $addressType, Institution $entity) {
            /** @var Location|null $location */
            $location = $this->em->getRepository(Location::class)
                ->find($addressType->getLocationId());

            $entity->setAddress(Address::create(
                $addressType->getStreet(),
                $addressType->getNumber(),
                $addressType->getPostcode(),
                $location
            ));
        });
        $filler->callback('nationalIdCard', function (NationalIdCardInputType $cardType, Institution $entity) {
            $entity->setNationalId(new NationalId());
            $entity->getNationalId()->setIdNumber($cardType->getNumber());
            $entity->getNationalId()->setIdType($cardType->getType());
        });
        $filler->callback('phone', function (PhoneInputType $phoneInputType, Institution $entity) {
            $entity->setPhone(new Phone());

            $filler = new ReflexiveFiller();
            $filler->fillBy($entity->getPhone(), $phoneInputType);
        });
        $filler->foreach('projectIds', function ($key, int $id, Institution $institution) {
            $project = $this->em->getRepository(Project::class)->find($id);
            if (!$project) {
                throw new EntityNotFoundException('project', $id);
            }
            return $project;
        });

        $filler->fillBy($institution, $inputType);

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
        
        $filler = new ReflexiveFiller();
        $filler->ignore(['projectIds', 'address', 'nationalIdCard', 'phone']);
        $filler->map('contactGivenName', 'contactName');
        $filler->map('contactFamilyName', 'contactFamilyName');
        $filler->fillBy($institution, $institutionType);
        
        if ($institutionType->getPhoneNumber()) {
            $institution->setPhone(new Phone());
            $institution->getPhone()->setType($institutionType->getPhoneType());
            $institution->getPhone()->setPrefix($institutionType->getPhonePrefix());
            $institution->getPhone()->setNumber($institutionType->getPhoneNumber());
        }


        if ($institutionType->getNationalId() !== null && !$institutionType->getNationalId()->isEmpty()) {
            $institution->setNationalId(new NationalId());

            $filler = new ReflexiveFiller();
            $filler->fillBy($institution->getPhone(), $institutionType->getNationalId());
        }

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

    /**
     * @param GlobalInputType\Country         $iso3
     * @param Institution                     $institution
     * @param InputType\UpdateInstitutionType $institutionType
     *
     * @return Institution
     * @throws \InvalidArgumentException
     *
     * @deprecated
     */
    public function updateDeprecated(GlobalInputType\Country $iso3, Institution $institution, InputType\UpdateInstitutionType $institutionType): Institution
    {
        if ($institution->getContact() == null) {
            $institution->setContact(new Person());
        }
        if (null !== $newValue = $institutionType->getName()) {
            $institution->setName($newValue);
        }
        if (null !== $newValue = $institutionType->getLongitude()) {
            $institution->setLongitude($newValue);
        }
        if (null !== $newValue = $institutionType->getLatitude()) {
            $institution->setLatitude($newValue);
        }
        if (null !== $newValue = $institutionType->getType()) {
            $institution->setType($newValue);
        }

        if ($institutionType->getNationalId() !== null) {
            if ($institution->getNationalId() == null) {
                $institution->setNationalId(new NationalId());
            }
            $institution->getNationalId()->setIdType($institutionType->getNationalId()->getType());
            $institution->getNationalId()->setIdNumber($institutionType->getNationalId()->getNumber());
        }
        if (null !== $newValue = $institutionType->getContactName()) {
            $institution->setContactName($newValue);
        }
        if (null !== $newValue = $institutionType->getContactFamilyName()) {
            $institution->setContactFamilyName($newValue);
        }
        if (null !== $institutionType->getPhoneNumber()) {
            if ($institution->getPhone() == null) {
                $institution->setPhone(new Phone());
            }
            $institution->getPhone()->setType($institutionType->getPhoneType());
            $institution->getPhone()->setPrefix($institutionType->getPhonePrefix());
            $institution->getPhone()->setNumber($institutionType->getPhoneNumber());
        }

        /** @var InputType\BeneficiaryAddressType $address */
        if (null !== $address = $institutionType->getAddress()) {
            $location = null;
            if ($address->getLocation() !== null) {
                $location = $this->locationService->getLocationByInputType($iso3, $address->getLocation());
            }
            $this->updateAddress($institution, Address::create(
                $address->getStreet(),
                $address->getNumber(),
                $address->getPostcode(),
                $location
                ));
        }

        $institution->setProjects(new ArrayCollection());
        foreach ($institutionType->getProjects() as $projectId) {
            $project = $this->em->getRepository(Project::class)->find($projectId);
            if (null === $project) {
                throw new \InvalidArgumentException("Project $projectId doesn't exist");
            }
            $institution->addProject($project);
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
