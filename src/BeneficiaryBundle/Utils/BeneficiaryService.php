<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\HHMember;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
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
    public function create(Household $household, Beneficiary $beneficiary)
    {
        $vulnerabilityCriterion = $this->saveVulnerabilityCriterion($beneficiary->getVulnerabilityCriterion(), false);
        $phones = $beneficiary->getPhones();
        $nationalIds = $beneficiary->getNationalIds();

        dump($phones);
        dump($nationalIds);
        dump($hhMembers);
        $beneficiary->setHousehold($household)
            ->setVulnerabilityCriterion($vulnerabilityCriterion)
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
        $vulnerabilityCriterion = $this->saveVulnerabilityCriterion($HHMember->getVulnerabilityCriterion());
        $HHMember->setBeneficiary($beneficiary)
            ->setVulnerabilityCriterion($vulnerabilityCriterion);
        $this->em->persist($HHMember);
        $this->em->flush();

        return $HHMember;
    }

    /**
     * @param Household $household
     * @return Household
     */
    public function saveHousehold(Household $household, $flush)
    {
        $location = $household->getLocation();
        $this->em->persist($location);
        $household->setLocation($location);
        $this->em->persist($household);

        $this->em->flush();
        return $household;
    }

    /**
     * @param VulnerabilityCriterion $vulnerabilityCriterion
     * @return VulnerabilityCriterion
     */
    public function saveVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion, $flush)
    {
        $this->em->persist($vulnerabilityCriterion);
        $this->em->flush();
        return $vulnerabilityCriterion;
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