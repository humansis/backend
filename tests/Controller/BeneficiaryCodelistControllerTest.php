<?php

namespace Tests\Controller;

use Entity\Referral;
use Entity\VulnerabilityCriterion;
use Enum\ResidencyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Enum\BeneficiaryType;
use Enum\NationalIdType;
use Enum\PhoneTypes;
use Tests\BMSServiceTestCase;

class BeneficiaryCodelistControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp(): void
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
    public function testGetBeneficiaryTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/types');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{
            "totalCount": ' . count(BeneficiaryType::values()) . ',
            "data": [
                {"code": "' . BeneficiaryType::HOUSEHOLD . '", "value": "' . BeneficiaryType::HOUSEHOLD . '"},
                {"code": "' . BeneficiaryType::BENEFICIARY . '", "value": "' . BeneficiaryType::BENEFICIARY . '"},
                {"code": "' . BeneficiaryType::COMMUNITY . '", "value": "' . BeneficiaryType::COMMUNITY . '"},
                {"code": "' . BeneficiaryType::INSTITUTION . '", "value": "' . BeneficiaryType::INSTITUTION . '"}
             ]
        }',
            $this->client->getResponse()->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetReferralTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/referral-types');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '{"totalCount": ' . count(Referral::REFERRALTYPES) . ', "data": [{"code": "*", "value": "*"}]}',
            $this->client->getResponse()->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetResidencyStatuses()
    {
        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/residency-statuses');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
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

        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/vulnerability-criteria');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
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
        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/national-ids/types');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(NationalIdType::values()), $result['totalCount']);
    }

    /**
     * @throws Exception
     */
    public function testGetPhoneTypes()
    {
        $this->request('GET', '/api/basic/web-app/v1/beneficiaries/phones/types');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(PhoneTypes::values()), $result['totalCount']);
    }
}
