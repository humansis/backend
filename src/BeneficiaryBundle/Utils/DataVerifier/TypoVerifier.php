<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

class TypoVerifier extends AbstractVerifier
{

    /**
     * Minimum percent to detect a similar household
     * @var int
     */
    private $minimumPercentSimilar = 90;


    public function verify(string $countryISO3, array $householdArray)
    {
        $listHouseholdsSaved = $this->em->getRepository(Household::class)->getAllBy($countryISO3);
        $newHead = null;
        foreach ($householdArray['beneficiaries'] as $newBeneficiaryArray)
        {
            if (1 === intval($newBeneficiaryArray['status']))
            {
                $newHead = $newBeneficiaryArray;
                break;
            }
        }
        if (null === $newHead)
            return null;

        // Concatenation of fields to compare with
        $stringNewHouseholdToCompare = $householdArray["address_street"] . "//" .
            $householdArray["address_number"] . "//" .
            $householdArray["address_postcode"] . "//" .
            $newHead["given_name"] . "//" .
            $newHead["family_name"];

        $similarHousehold = null;
        $percent = $this->minimumPercentSimilar;
        /** @var Household $oldHousehold */
        foreach ($listHouseholdsSaved as $oldHousehold)
        {
            // Get the head of the current household
            /** @var Beneficiary $oldHead */
            $oldHead = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($oldHousehold);
            if (!$oldHead instanceof Beneficiary)
                continue;

            $stringOldHouseholdToCompare = $oldHousehold->getAddressStreet() . "//" .
                $oldHousehold->getAddressNumber() . "//" .
                $oldHousehold->getAddressPostcode() . "//" .
                $oldHead->getGivenName() . "//" .
                $oldHead->getFamilyName();


            similar_text(
                $stringNewHouseholdToCompare,
                $stringOldHouseholdToCompare,
                $tmpPercent
            );

            if (100 == $tmpPercent)
            {
                return $oldHousehold;
            }
            elseif ($percent < $tmpPercent)
            {
                $similarHousehold = $oldHousehold;
                $percent = $tmpPercent;
            }
        }
        if ($this->minimumPercentSimilar < $percent)
            return $similarHousehold;
        else
            return null;
    }
}