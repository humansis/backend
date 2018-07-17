<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

class MoreVerifier extends AbstractVerifier
{


    /** @var string $token */
    private $token;

    public function __construct(
        EntityManagerInterface $entityManager,
        string &$token = null)
    {
        parent::__construct($entityManager);
        $this->token = $token;
    }

    /**
     * @param string $countryISO3
     * @param array $householdArray
     * @return array|null
     */
    public function verify(string $countryISO3, array $householdArray)
    {
        if (count($householdArray['new']['beneficiaries']) > count($householdArray['old']['beneficiaries']))
            return [
                'new' => $householdArray['new'],
                'old' => $this->em->getRepository(Household::class)->find($householdArray['old']['id'])
            ];

        return null;
    }
}