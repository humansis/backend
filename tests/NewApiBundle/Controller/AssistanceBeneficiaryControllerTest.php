<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Enum\AssistanceTargetType;
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

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc');

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

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "institutionId": "*",
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

        $this->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities?sort[]=id.desc');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment('{
            "totalCount": "*", 
            "data": [
                {
                    "id": "*",
                    "communityId": "*",
                    "removed": "*",
                    "justification": "*",
                    "generalReliefItemIds": "*",
                    "transactionIds": "*",
                    "smartcardDepositIds": "*",
                    "bookletIds": "*"
                }
            ]}', $this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testAddBeneficiaryToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validated' => true,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INDIVIDUAL,
        ], ['id' => 'asc']);
        $beneficiary = $em->getRepository(Beneficiary::class)->findOneBy([], ['id'=>'desc']);

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiary->getId()],
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $beneficiary->getId()];
    }

    /**
     * @depends testAddBeneficiaryToAssistance
     */
    public function testRemoveBeneficiaryFromAssistance($data)
    {
        list($assistanceId, $beneficiaryId) = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiaryId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testAddInstitutionToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validated' => true,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INSTITUTION,
        ], ['id' => 'asc']);
        $institution = $em->getRepository(Institution::class)->findOneBy([], ['id'=>'desc']);

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-institutions', [
            'institutionIds' => [$institution->getId()],
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $institution->getId()];
    }

    /**
     * @depends testAddInstitutionToAssistance
     */
    public function testRemoveInstitutionFromAssistance($data)
    {
        list($assistanceId, $institutionId) = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions', [
            'institutionIds' => [$institutionId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testAddCommunityToAssistance()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validated' => true,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::COMMUNITY,
        ], ['id' => 'asc']);
        $community = $em->getRepository(Community::class)->findOneBy([], ['id'=>'desc']);

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-communities', [
            'communityIds' => [$community->getId()],
            'justification' => 'test',
            'added' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        return [$assistance->getId(), $community->getId()];
    }

    /**
     * @depends testAddCommunityToAssistance
     */
    public function testRemoveCommunityFromAssistance($data)
    {
        list($assistanceId, $communityId) = $data;

        $this->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities', [
            'communityIds' => [$communityId],
            'justification' => 'test',
            'removed' => true,
        ]);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );
    }
}
