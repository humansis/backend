<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Exception;
use Tests\BMSServiceTestCase;

class AssistanceBeneficiaryControllerTest extends BMSServiceTestCase
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

    public function testGetAssistanceBeneficiariesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Beneficiary::class, 'b')
                ->join('b.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->where('b.archived = 0')
                ->andWhere('a.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with beneficiary in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "beneficiaryId": "*",
                    "removed": "*",
                    "justification": "*",
                    "generalReliefItemIds": "*",
                    "transactionIds": "*",
                    "smartcardDepositIds": "*",
                    "bookletIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    public function testGetAssistanceInstitutionsByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Institution::class, 'i')
                ->join('i.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->andWhere('a.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with institution in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/assistances/'.$assistanceId.'/assistances-institutions?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "beneficiaryId": "*",
                    "removed": "*",
                    "justification": "*",
                    "generalReliefItemIds": "*",
                    "transactionIds": "*",
                    "smartcardDepositIds": "*",
                    "bookletIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    public function testGetAssistanceCommunitiesByAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $assistanceId = $em->createQueryBuilder()
                ->select('a.id')
                ->from(Community::class, 'c')
                ->join('c.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->andWhere('a.archived = 0')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one assistance with community in database to complete this test.');
            return;
        }

        $this->request('GET', '/api/basic/assistances/'.$assistanceId.'/assistances-communities?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "beneficiaryId": "*",
                    "removed": "*",
                    "justification": "*",
                    "generalReliefItemIds": "*",
                    "transactionIds": "*",
                    "smartcardDepositIds": "*",
                    "bookletIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }
}
