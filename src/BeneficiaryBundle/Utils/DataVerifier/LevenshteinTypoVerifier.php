<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

class LevenshteinTypoVerifier extends AbstractVerifier
{

    /**
     * Maximum distance between two strings with the Levenshtein algorithm
     * @var int
     */
    private $maximumDistanceLevenshtein = 3;

    /** @var Container $container */
    private $container;

    private $token;

    /** @var array $listHouseholdsSaved */
    private $listHouseholdsSaved;

    /** @var array $mappingHouseholdAndHead */
    private $mappingHouseholdAndHead;


    public function __construct(EntityManagerInterface $entityManager, Container $container, $token)
    {
        parent::__construct($entityManager);

        $this->token = $token;
        $this->container = $container;
    }

    public function verify(string $countryISO3, array $householdArray, int $cacheId)
    {
        $householdRepository = $this->em->getRepository(Household::class);
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


        dump($householdRepository->foundSimilarLevenshtein(
            $householdArray["address_street"] .
            $householdArray["address_number"] .
            $householdArray["address_postcode"] .
            $newHead["given_name"] .
            $newHead["family_name"],
            $this->maximumDistanceLevenshtein)
        );
        return null;
    }
}