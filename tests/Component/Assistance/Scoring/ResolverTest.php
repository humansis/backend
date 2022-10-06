<?php

declare(strict_types=1);

namespace Tests\Component\Assistance\Scoring;

use Entity\Household;
use Doctrine\Persistence\ObjectManager;
use Component\Assistance\Scoring\Enum\ScoringRuleType;
use Component\Assistance\Scoring\Model\Factory\ScoringFactory;
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

    /** @var ScoringFactory */
    private $scoringFactory;

    public function __construct()
    {
        parent::__construct();

        $kernel = $this->bootKernel();

        $this->resolver = $kernel->getContainer()->get(ScoringResolver::class);
        $this->objectManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->scoringFactory = $kernel->getContainer()->get(ScoringFactory::class);
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
}
