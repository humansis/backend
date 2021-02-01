<?php

namespace CommonBundle\DataFixtures;

use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;
use TransactionBundle\Entity\Transaction;
use TransactionBundle\Utils\Provider\KHMFinancialProvider;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var string */
    private $environment;

    /** @var KHMFinancialProvider */
    private $KHMFinancialProvider;

    /**
     * @param KernelInterface      $kernel
     * @param KHMFinancialProvider $KHMFinancialProvider
     */
    public function __construct(KernelInterface $kernel, KHMFinancialProvider $KHMFinancialProvider)
    {
        $this->environment = $kernel->getEnvironment();
        $this->KHMFinancialProvider = $KHMFinancialProvider;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production environment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        srand(42);

        foreach ($manager->getRepository(AssistanceBeneficiary::class)->findAll() as $ab) {
            for ($j = 0; $j < rand(0, 2); ++$j) {
                $this->generateNoPhoneTransaction($ab, $manager);
            }
            if ($ab->getId() % 4 === 0) {
                $this->generateFailureTransaction($ab, $manager);
            } else {
                $this->generateValidTransaction($ab, $manager);
            }
        }

        $manager->flush();
    }

    private function generateNoPhoneTransaction(AssistanceBeneficiary $ab, ObjectManager $manager): Transaction
    {
        $days = new \DateInterval("P".rand(30, 200)."D");
        /** @see UserFixtures */
        $user = $this->getReference('user_admin');

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($ab);
        $transaction->setDateSent((new \DateTime())->sub($days),);
        $transaction->setTransactionId(self::generateSerialNumber());
        $transaction->setAmountSent(rand(10, 10000));
        $transaction->setTransactionStatus(Transaction::NO_PHONE);
        $transaction->setMessage("no phone");
        $transaction->setSentBy($user);

        $ab->addTransaction($transaction);
        $user->addTransaction($transaction);

        $manager->persist($transaction);

        return $transaction;
    }

    private static function generateSerialNumber()
    {
        static $i = 0;

        return substr(md5(++$i), 0, 7);
    }

    private function generateFailureTransaction(AssistanceBeneficiary $ab, ObjectManager $manager): Transaction
    {
        $days = new \DateInterval("P".rand(0, 30)."D");
        /** @see UserFixtures */
        $user = $this->getReference('user_admin');

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($ab);
        $transaction->setDateSent((new \DateTime())->sub($days),);
        $transaction->setTransactionId(self::generateSerialNumber());
        $transaction->setAmountSent(rand(10, 10000));
        $transaction->setTransactionStatus(Transaction::FAILURE);
        $transaction->setMessage("some error message from third party");
        $transaction->setSentBy($user);

        $ab->addTransaction($transaction);
        $user->addTransaction($transaction);

        $manager->persist($transaction);

        return $transaction;
    }

    private function generateValidTransaction(AssistanceBeneficiary $ab, ObjectManager $manager): Transaction
    {
        $days = new \DateInterval("P".rand(0, 30)."D");
        /** @see UserFixtures */
        $user = $this->getReference('user_admin');

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($ab);
        $transaction->setDateSent((new \DateTime())->sub($days),);
        $transaction->setTransactionId(self::generateSerialNumber());
        $transaction->setAmountSent(rand(10, 10000));
        $transaction->setTransactionStatus(Transaction::SUCCESS);
        $transaction->setMessage("Valid tr. from fixtures");
        $transaction->setSentBy($user);

        $ab->addTransaction($transaction);
        $user->addTransaction($transaction);

        $manager->persist($transaction);

        return $transaction;
    }

    public function getDependencies()
    {
        return [
            BeneficiaryTestFixtures::class,
            AssistanceFixtures::class,
            UserFixtures::class,
        ];
    }
}
