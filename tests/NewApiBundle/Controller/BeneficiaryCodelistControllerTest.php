<?php

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use NewApiBundle\Enum\PhoneTypes;
use Tests\BMSServiceTestCase;

class BeneficiaryCodelistControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    /**
     * @throws Exception
     */
    public function testGetReferralTypes()
    {
        $this->request('GET', '/api/basic/beneficiaries/referral-types');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": '.count(Referral::REFERRALTYPES).', "data": [{"code": "*", "value": "*"}]}',
            $this->client->getResponse()->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetResidencyStatuses()
    {
        $this->request('GET', '/api/basic/beneficiaries/residency-statuses');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(ResidencyStatus::all()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetVulnerabilityCriterion()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $this->request('GET', '/api/basic/beneficiaries/vulnerability-criteria');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);

        $criterion = $em->getRepository(VulnerabilityCriterion::class)->findAllActive();
        $this->assertEquals(count($criterion), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetNationalIdsTypes()
    {
        $this->request('GET', '/api/basic/beneficiaries/national-ids/types');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(NationalId::types()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetPhoneTypes()
    {
        $this->request('GET', '/api/basic/beneficiaries/phones/types');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(PhoneTypes::values()), $result['totalCount']);
    }
}
