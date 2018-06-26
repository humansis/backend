<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
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
     * @param Household $household
     * @param Beneficiary $beneficiary
     * @return Beneficiary
     */
    public function create(Household $household, Beneficiary $beneficiary, $flush)
    {
        $vulnerabilityCriterions = $beneficiary->getVulnerabilityCriterions();
        if (null !== $vulnerabilityCriterions)
        {
            foreach ($vulnerabilityCriterions as $vulnerabilityCriterion)
            {
                $this->saveVulnerabilityCriterion($vulnerabilityCriterion, false);
            }
        }

        $phones = $beneficiary->getPhones();
        if (null !== $phones)
        {
            foreach ($phones as $phone)
            {
                $this->savePhone($beneficiary, $phone, false);
            }
        }

        $nationalIds = $beneficiary->getNationalIds();
        if (null !== $nationalIds)
        {
            foreach ($nationalIds as $nationalId)
            {
                $this->saveNationalId($beneficiary, $nationalId, false);
            }
        }
        $beneficiary->setHousehold($household);


        $this->em->persist($beneficiary);
        if ($flush)
        $this->em->flush();
        return $beneficiary;

    }

    /**
     * @param VulnerabilityCriterion $vulnerabilityCriterion
     * @return VulnerabilityCriterion
     */
    public function saveVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion, $flush)
    {
        $this->em->persist($vulnerabilityCriterion);
        if ($flush)
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
        if ($flush)
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
        if ($flush)
            $this->em->flush();
        return $nationalId;
    }
}