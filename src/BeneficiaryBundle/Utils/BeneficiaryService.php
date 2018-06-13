<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\BeneficiaryProfile;
use BeneficiaryBundle\Entity\HHMember;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriteria;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;

class BeneficiaryService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var Serializer $serializer */
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer)
    {
        $this->em = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * @param array $beneficiaryArray
     * @return Beneficiary
     */
    public function create(array $beneficiaryArray)
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->serializer->deserialize(json_encode($beneficiaryArray), Beneficiary::class, 'json');

        $beneficiaryProfile = $this->saveBeneficiaryProfile($beneficiary->getBeneficiaryProfile(), false);
        $vulnerabilityCriteria = $this->saveVulnerabilityCriteria($beneficiary->getVulnerabilityCriteria(), false);
        $phones = $beneficiary->getPhones();
        $nationalIds = $beneficiary->getNationalIds();
        $hhMembers = $beneficiary->getHhMembers();

        dump($phones);
        dump($nationalIds);
        dump($hhMembers);
        $beneficiary->setBeneficiaryProfile($beneficiaryProfile)
            ->setVulnerabilityCriteria($vulnerabilityCriteria)
            ->setHhMembers(null);

        $this->em->persist($beneficiary);
        foreach ($phones as $phone)
        {
            $this->savePhone($beneficiary, $phone, false);
        }
        foreach ($nationalIds as $nationalId)
        {
            $this->saveNationalId($beneficiary, $nationalId, false);
        }
        foreach ($hhMembers as $hhMember)
        {
            $this->saveHHMember($beneficiary, $hhMember, false);
        }


        $this->em->flush();
        return $beneficiary;
    }

    public function saveHHMember(Beneficiary $beneficiary, HHMember $HHMember, $flush)
    {
        $vulnerabilityCriteria = $this->saveVulnerabilityCriteria($HHMember->getVulnerabilityCriteria());
        $HHMember->setBeneficiary($beneficiary)
            ->setVulnerabilityCriteria($vulnerabilityCriteria);
        $this->em->persist($HHMember);
        $this->em->flush();

        return $HHMember;
    }

    /**
     * @param BeneficiaryProfile $beneficiaryProfile
     * @return BeneficiaryProfile
     */
    public function saveBeneficiaryProfile(BeneficiaryProfile $beneficiaryProfile, $flush)
    {
        $location = $beneficiaryProfile->getLocation();
        $this->em->persist($location);
        $beneficiaryProfile->setLocation($location);
        $this->em->persist($beneficiaryProfile);

        $this->em->flush();
        return $beneficiaryProfile;
    }

    /**
     * @param VulnerabilityCriteria $vulnerabilityCriteria
     * @return VulnerabilityCriteria
     */
    public function saveVulnerabilityCriteria(VulnerabilityCriteria $vulnerabilityCriteria, $flush)
    {
        $this->em->persist($vulnerabilityCriteria);
        $this->em->flush();
        return $vulnerabilityCriteria;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param Phone $phone
     * @return Phone
     */
    public function savePhone(Beneficiary $beneficiary, Phone $phone, $flush)
    {
        $phone->setBeneficiary($beneficiary);
        $this->em->persist($phone);
        $this->em->flush();
        return $phone;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param NationalId $nationalId
     * @return NationalId
     */
    public function saveNationalId(Beneficiary $beneficiary, NationalId $nationalId, $flush)
    {
        $nationalId->setBeneficiary($beneficiary);
        $this->em->persist($nationalId);
        $this->em->flush();
        return $nationalId;
    }

    public function update(Beneficiary $beneficiary, array $beneficiaryArray)
    {

    }
}