<?php

namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use PhpOption\Tests\PhpOptionRepo;

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
     * @param $flush
     * @return Beneficiary
     * @throws \Exception
     */
    public function create(Household $household, Beneficiary $beneficiary, $flush)
    {
        $vulnerabilityCriterions = $beneficiary->getVulnerabilityCriterions();
        $beneficiary->setVulnerabilityCriterions(null);
        if (null !== $vulnerabilityCriterions)
        {
            foreach ($vulnerabilityCriterions as $vulnerabilityCriterion)
            {
                $beneficiary->addVulnerabilityCriterion($this->getVulnerabilityCriterion($vulnerabilityCriterion));
            }
        }

        $phones = $beneficiary->getPhones();
        if (null !== $phones)
        {
            foreach ($phones as $phone)
            {
                $this->getOrSavePhone($beneficiary, $phone, false);
            }
        }

        $nationalIds = $beneficiary->getNationalIds();
        if (null !== $nationalIds)
        {
            foreach ($nationalIds as $nationalId)
            {
                $this->getOrSaveNationalId($beneficiary, $nationalId, false);
            }
        }

        $beneficiary->setHousehold($household);

        $this->em->persist($beneficiary);
        if ($flush)
            $this->em->flush();
        return $beneficiary;
    }

    public function update(Household $oldHousehold, Household $newHousehold, $flush)
    {

    }

    /**
     * @param VulnerabilityCriterion $vulnerabilityCriterion
     * @return VulnerabilityCriterion
     * @throws \Exception
     */
    public function getVulnerabilityCriterion(VulnerabilityCriterion $vulnerabilityCriterion)
    {
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        $vulnerabilityCriterion = $this->em->getRepository(VulnerabilityCriterion::class)->find($vulnerabilityCriterion);

        if (!$vulnerabilityCriterion instanceof VulnerabilityCriterion)
            throw new \Exception("This vulnerability doesn't exist.");
        return $vulnerabilityCriterion;
    }

    /**
     * @param Beneficiary $beneficiary
     * @param Phone $phone
     * @return Phone
     */
    public function getOrSavePhone(Beneficiary $beneficiary, Phone $phone, $flush)
    {
        if ($phone->getId() != null)
        {
            /** @var Phone $oldPhone */
            $oldPhone = $this->em->getRepository(Phone::class)->find($phone);
            $oldPhone->setType($phone->getType())
                ->setNumber($phone->getNumber())
                ->setBeneficiary($phone->getBeneficiary());

            $this->em->persist($oldPhone);
            if ($flush)
                $this->em->flush();
            return $oldPhone;
        }
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
    public function getOrSaveNationalId(Beneficiary $beneficiary, NationalId $nationalId, $flush)
    {
        if ($nationalId->getId() != null)
        {
            /** @var NationalId $oldNationalId */
            $oldNationalId = $this->em->getRepository(NationalId::class)->find($nationalId);
            $oldNationalId->setIdNumber($nationalId->getIdNumber())
                ->setIdType($nationalId->getIdType())
                ->setBeneficiary($nationalId->getBeneficiary());

            $this->em->persist($oldNationalId);
            if ($flush)
                $this->em->flush();

            return $oldNationalId;
        }
        $nationalId->setBeneficiary($beneficiary);
        $this->em->persist($nationalId);
        if ($flush)
            $this->em->flush();
        return $nationalId;
    }
}