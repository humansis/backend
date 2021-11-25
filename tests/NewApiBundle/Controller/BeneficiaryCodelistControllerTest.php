<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Referral;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Enum\ResidencyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use NewApiBundle\Enum\BeneficiaryType;
use NewApiBundle\Enum\PhoneTypes;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class BeneficiaryCodelistControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetBeneficiaryTypes()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/types', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": '.count(BeneficiaryType::values()).', 
            "data": [
                {"code": "'.BeneficiaryType::HOUSEHOLD.'", "value": "'.BeneficiaryType::HOUSEHOLD.'"},
                {"code": "'.BeneficiaryType::BENEFICIARY.'", "value": "'.BeneficiaryType::BENEFICIARY.'"},
                {"code": "'.BeneficiaryType::COMMUNITY.'", "value": "'.BeneficiaryType::COMMUNITY.'"},
                {"code": "'.BeneficiaryType::INSTITUTION.'", "value": "'.BeneficiaryType::INSTITUTION.'"}
             ]
        }', $this->client->getResponse()->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testGetReferralTypes()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/referral-types', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/residency-statuses', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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

        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/vulnerability-criteria', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/national-ids/types', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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
        $this->client->request('GET', '/api/basic/web-app/v1/beneficiaries/phones/types', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertEquals(count(PhoneTypes::values()), $result['totalCount']);
    }
}
