<?php


namespace Tests\Repository;


use Entity\Beneficiary;
use Entity\Household;
use Repository\BeneficiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BeneficiaryRepositoryTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private $em;


    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }


    public function countByHouseholdDataProvider()
    {
        return [
            '1 beneficiary' => [1, 1],
            '4 beneficiaries' => [3, 4],
        ];
    }


    /**
     * @param int $householdId
     * @param int $expectedBeneficiariesCount
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @dataProvider countByHouseholdDataProvider
     */
    public function testCountByHousehold(int $householdId, int $expectedBeneficiariesCount)
    {
        /** @var Household $household */
        $household = $this->em->getRepository(Household::class)
            ->find($householdId);

        /** @var BeneficiaryRepository $beneficiaryRepository */
        $beneficiaryRepository = $this->em->getRepository(Beneficiary::class);

        $beneficiariesCount = $beneficiaryRepository->countByHousehold($household);

        $this->assertEquals($expectedBeneficiariesCount, $beneficiariesCount);
    }


    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null;
    }
}
