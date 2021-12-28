<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use Tests\NewApiBundle\Helper\AssertIterablesTrait;

class AssistanceBeneficiaryControllerTest extends AbstractFunctionalApiTest
{
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

        $this->client->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries?sort[]=id.desc', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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

        $this->client->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions?sort[]=id.desc', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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

        $this->client->request('GET', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities?sort[]=id.desc', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
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

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiary->getId()],
            'justification' => 'test',
            'added' => true,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        return [$assistance->getId(), $beneficiary->getId()];
    }

    /**
     * @depends testAddBeneficiaryToAssistance
     */
    public function testRemoveBeneficiaryFromAssistance($data)
    {
        list($assistanceId, $beneficiaryId) = $data;

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-beneficiaries', [
            'beneficiaryIds' => [$beneficiaryId],
            'justification' => 'test',
            'removed' => true,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testAddInstitutionToAssistance()
    {
        $this->markTestIncomplete('Skipped because unstable data');
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validated' => true,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::INSTITUTION,
        ], ['id' => 'asc']);
        $institution = $em->getRepository(Institution::class)->findOneBy([], ['id'=>'desc']);

        // clean assistance data
        $assistanceBeneficiarys = $em->getRepository(AssistanceBeneficiary::class)
            ->findBy(['beneficiary' => $institution, 'assistance' => $assistance]);
        foreach ($assistanceBeneficiarys as $assistanceBeneficiary){
            $em->remove($assistanceBeneficiary);
        }

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-institutions', [
            'institutionIds' => [$institution->getId()],
            'justification' => 'test',
            'added' => true,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        return [$assistance->getId(), $institution->getId()];
    }

    /**
     * @depends testAddInstitutionToAssistance
     */
    public function testRemoveInstitutionFromAssistance($data)
    {
        list($assistanceId, $institutionId) = $data;

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-institutions', [
            'institutionIds' => [$institutionId],
            'justification' => 'test',
            'removed' => true,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
    }

    /**
     * @throws Exception
     */
    public function testAddCommunityToAssistance()
    {
        $this->markTestIncomplete('Skipped because unstable data');
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $assistance = $em->getRepository(Assistance::class)->findOneBy([
            'validated' => true,
            'completed' => false,
            'archived' => false,
            'targetType' => AssistanceTargetType::COMMUNITY,
        ], ['id' => 'asc']);
        $community = $em->getRepository(Community::class)->findOneBy([], ['id'=>'desc']);

        // clean assistance data
        $assistanceBeneficiarys = $em->getRepository(AssistanceBeneficiary::class)
            ->findBy(['beneficiary' => $community, 'assistance' => $assistance]);
        foreach ($assistanceBeneficiarys as $assistanceBeneficiary){
            $em->remove($assistanceBeneficiary);
        }
        $em->flush();

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistance->getId().'/assistances-communities', [
            'communityIds' => [$community->getId()],
            'justification' => 'test',
            'added' => true,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        return [$assistance->getId(), $community->getId()];
    }

    /**
     * @depends testAddCommunityToAssistance
     */
    public function testRemoveCommunityFromAssistance($data)
    {
        list($assistanceId, $communityId) = $data;

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$assistanceId.'/assistances-communities', [
            'communityIds' => [$communityId],
            'justification' => 'test',
            'removed' => true,
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
    }
}
