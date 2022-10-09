<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring;

use Entity\CountrySpecific;
use Entity\CountrySpecificAnswer;
use Entity\Household;
use Doctrine\Persistence\ObjectManager;
use Component\Assistance\Scoring\Enum\ScoringRuleType;
use Component\Assistance\Scoring\Model\Scoring;
use Component\Assistance\Scoring\Model\ScoringRule;
use Component\Assistance\Scoring\Model\ScoringRuleOption;
use Component\Assistance\Scoring\ScoringResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResolverTest extends KernelTestCase
{
    /** @var ScoringResolver */
    private $resolver;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct()
    {
        parent::__construct();

        $kernel = $this->bootKernel();

        $this->resolver = $kernel->getContainer()->get(ScoringResolver::class);
        $this->objectManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    public function testSimpleCountrySpecific(): void
    {
        /** @var Household $household */
        $household = $this->objectManager->getRepository(Household::class)->findOneBy([]);

        $countrySpecificName = $household->getCountrySpecificAnswers()->first()->getCountrySpecific()->getFieldString();
        $countrySpecificAnswer = $household->getCountrySpecificAnswers()->first()->getAnswer();

        $scoringRule = new ScoringRule(ScoringRuleType::COUNTRY_SPECIFIC, $countrySpecificName, 'Test Rule');
        $scoringRule->addOption(new ScoringRuleOption($countrySpecificAnswer, 5));
        $scoringRule->addOption(new ScoringRuleOption('dummy value', 1));

        $scoring = new Scoring('Test scoring', [$scoringRule]);

        $protocol = $this->resolver->compute($household, $scoring, 'KHM');

        $this->assertEquals(5, $protocol->getTotalScore());
        $this->assertEquals(5, $protocol->getScore('Test Rule'));
    }

    public function testCountrySpecificWithExpressionEvaluation()
    {
        /** @var Household $household */
        $household = $this->objectManager->getRepository(Household::class)->findOneBy([]);

        $CSO = new CountrySpecific('Test cso', 'number', 'SYR');
        $CSO->setCountryIso3('SYR');

        $countrySpecificAnswer = new CountrySpecificAnswer();
        $countrySpecificAnswer->setCountrySpecific($CSO);
        $countrySpecificAnswer->setHousehold($household);
        $countrySpecificAnswer->setAnswer('5');

        $this->objectManager->persist($CSO);
        $this->objectManager->persist($countrySpecificAnswer);
        $this->objectManager->flush();

        $scoringRule = new ScoringRule(ScoringRuleType::COUNTRY_SPECIFIC, 'Test cso', 'Test Rule');
        $scoringRule->addOption(new ScoringRuleOption('5', 1));
        $scoringRule->addOption(new ScoringRuleOption('x < 5', 2));
        $scoringRule->addOption(new ScoringRuleOption('x > 5', 3));

        $scoring = new Scoring('Test scoring', [$scoringRule]);

        $protocol = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(1, $protocol->getTotalScore());

        $countrySpecificAnswer->setAnswer('4');
        $this->objectManager->flush();

        $protocol = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(2, $protocol->getTotalScore());

        $countrySpecificAnswer->setAnswer('6');
        $this->objectManager->flush();

        $protocol = $this->resolver->compute($household, $scoring, 'SYR');
        $this->assertEquals(3, $protocol->getTotalScore());

        $this->objectManager->remove($countrySpecificAnswer);
        $this->objectManager->remove($CSO);
        $this->objectManager->flush();
    }
}
