<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\Container;

class TypoVerifier extends AbstractVerifier
{

    /**
     * Minimum percent to detect a similar household
     * @var int
     */
    private $minimumPercentSimilar = 90;

    private $token;

    private $container;


    public function __construct(EntityManagerInterface $entityManager, Container $container, $token)
    {
        parent::__construct($entityManager);

        $this->token = $token;
        $this->container = $container;
    }


    /**
     * @param string $countryISO3
     * @param array $householdArray
     * @return Household|bool|null
     * @throws \Exception
     */
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
                // SAVE 100% SIMILAR IN 1_typo
                $this->saveInCache('mapping_new_old', $householdArray, $oldHousehold);
                return false;
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

    /**
     * @param string $step
     * @param array $dataToSave
     * @param Household $household
     * @throws \Exception
     */
    private function saveInCache(string $step, array $dataToSave, Household $household)
    {
        $arrayNewHousehold = json_decode($this->container->get('jms_serializer')
            ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true)), true);

        $sizeToken = 50;
        if (null === $this->token)
            $this->token = bin2hex(random_bytes($sizeToken));

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        if (!is_file($dir_var . '/' . $step))
        {
            $dataToSave['id_tmp_cache'] = 0;
            file_put_contents($dir_var . '/' . $step, json_encode([["new" => $dataToSave, "old" => $arrayNewHousehold]]));
        }
        else
        {
            $listHH = json_decode(file_get_contents($dir_var . '/' . $step), true);
            $index = count($listHH);
            $dataToSave['id_tmp_cache'] = $index;
            $listHH[$index] = ["new" => $dataToSave, "old" => $arrayNewHousehold];
            file_put_contents($dir_var . '/' . $step, json_encode($listHH));
        }

    }
}