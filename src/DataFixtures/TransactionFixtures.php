<?php

namespace DataFixtures;

use DateInterval;
use DateTime;
use Entity\Beneficiary;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Entity\Transaction;
use Utils\Provider\KHMFinancialProvider;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly string $environment, private readonly KHMFinancialProvider $KHMFinancialProvider)
    {
    }

    public function load(ObjectManager $manager)
    {
        if ('prod' === $this->environment) {
            // this fixtures are not for production environment
            return;
        }

        // set up seed will make random values will be same for each run of fixtures
        mt_srand(42);

        /** @var AssistanceBeneficiary $ab */
        foreach ($this->getAssistanceBeneficiaries($manager) as $ab) {
            for ($j = 0; $j < random_int(0, 2); ++$j) {
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
        $days = new DateInterval("P" . random_int(30, 200) . "D");
        /** @see UserFixtures */
        $user = $this->getReference('user_admin');

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($ab);
        $transaction->setDateSent((new DateTime())->sub($days));
        $transaction->setTransactionId(self::generateSerialNumber());
        $transaction->setAmountSent(random_int(10, 10000));
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
        $days = new DateInterval("P" . random_int(0, 30) . "D");
        /** @see UserFixtures */
        $user = $this->getReference('user_admin');

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($ab);
        $transaction->setDateSent((new DateTime())->sub($days));
        $transaction->setTransactionId(self::generateSerialNumber());
        $transaction->setAmountSent(random_int(10, 10000));
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
        $days = new DateInterval("P" . random_int(0, 30) . "D");
        /** @see UserFixtures */
        $user = $this->getReference('user_admin');

        $transaction = new Transaction();
        $transaction->setAssistanceBeneficiary($ab);
        $transaction->setDateSent((new DateTime())->sub($days));
        $transaction->setTransactionId(self::generateSerialNumber());
        $transaction->setAmountSent(random_int(10, 10000));
        $transaction->setTransactionStatus(Transaction::SUCCESS);
        $transaction->setMessage("Valid tr. from fixtures");
        $transaction->setSentBy($user);

        $ab->addTransaction($transaction);
        $user->addTransaction($transaction);

        $manager->persist($transaction);

        return $transaction;
    }

    private function getAssistanceBeneficiaries(ObjectManager $manager): array
    {
        $validatedAssists = $manager->getRepository(Assistance::class)->matching(
            Criteria::create()
                ->where(Criteria::expr()->neq('validatedBy', null))
                ->orderBy(['id' => 'asc'])
        )->toArray();

        return $manager->getRepository(AssistanceBeneficiary::class)->findBy(
            ['assistance' => $validatedAssists],
            ['id' => 'asc'],
            100,
        );
    }

    public function getDependencies()
    {
        return [
            BeneficiaryTestFixtures::class,
            AssistanceFixtures::class,
            UserFixtures::class,
            AssistanceValidationFixtures::class,
        ];
    }
}
