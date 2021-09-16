<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\IdentityChecker;
use NewApiBundle\Entity\Import;
use NewApiBundle\Entity\ImportQueueDuplicity;
use NewApiBundle\Enum\ImportState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IdentityCheckerTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private static $entityManager;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        self::$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testSelfCheck()
    {
        $this->markTestSkipped('Self check is disabled for this time');
        $import = self::$entityManager->getRepository(Import::class)->findBy(['title' => 'test_fixtures'])[0];
        $import->setState(ImportState::IDENTITY_CHECKING);

        $checker = new IdentityChecker(self::$entityManager);
        $checker->check($import);

        $count = self::$entityManager->createQueryBuilder()
            ->select('count(iqd)')
            ->from(ImportQueueDuplicity::class, 'iqd')
            ->leftJoin('iqd.ours', 'ours')
            ->leftJoin('iqd.theirs', 'theirs')
            ->leftJoin('ours.import', 'i')
            ->where('i.id = ?1')
            ->setParameter(1, $import->getId())
            ->getQuery()->getSingleScalarResult();

        $this->assertGreaterThan(0, $count);
    }
}
