<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use ProjectBundle\Entity\Project;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\Booklet;

class BookletControllerTest extends AbstractFunctionalApiTest
{
    public function testCreate()
    {
        $project = self::$container->get('doctrine')->getRepository(Project::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('POST', '/api/basic/web-app/v1/booklets/batches', [
            'iso3' => 'KHM',
            'quantityOfBooklets' => 5,
            'quantityOfVouchers' => 2,
            'values' => [333],
            'projectId' => $project->getId(),
            'password' => null,
            'currency' => 'CZK',
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
    }

    public function testUpdate()
    {
        $booklet = self::$container->get('doctrine')->getRepository(Booklet::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('PUT', '/api/basic/web-app/v1/booklets/'.$booklet->getId(), [
            'quantityOfVouchers' => 2,
            'values' => [333],
            'password' => null,
            'currency' => 'CZK',
        ], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
    }

    /**
     * @depends testCreate
     */
    public function testGet()
    {
        $booklet = self::$container->get('doctrine')->getRepository(Booklet::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/booklets/'.$booklet->getId(), [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('projectId', $result);
        $this->assertArrayHasKey('beneficiaryId', $result);
        $this->assertArrayHasKey('assistanceId', $result);
        $this->assertArrayHasKey('totalValue', $result);
        $this->assertArrayHasKey('individualValues', $result);
        $this->assertArrayHasKey('quantityOfVouchers', $result);
        $this->assertArrayHasKey('deletable', $result);
        $this->assertArrayHasKey('distributed', $result);
    }

    /**
     * @depends testCreate
     */
    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/booklets?sort[]=value.asc&filter[fulltext]=KHM', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        $booklet = self::$container->get('doctrine')->getRepository(Booklet::class)->findBy([], ['id' => 'desc'], 1)[0];

        $this->client->request('DELETE', '/api/basic/web-app/v1/booklets/'.$booklet->getId(), [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());
    }

    public function testAssignToBeneficiary()
    {
        $doctrine = self::$container->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $result = $em->createQueryBuilder()
                ->select('b.id AS beneficiaryId')
                ->addSelect('a.id AS assistanceId')
                ->from(Beneficiary::class, 'b')
                ->join('b.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('There needs to be at least one beneficiary assigned to an assistance to complete this test');
            return;
        }

        $booklet = $doctrine->getRepository(Booklet::class)->findBy(['status' => Booklet::UNASSIGNED], ['id' => 'asc'])[0];

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$result['assistanceId'].'/beneficiaries/'.$result['beneficiaryId'].'/booklets/'.$booklet->getCode(), [], [], $this->addAuth());

        $this->assertTrue(
            $this->client->getResponse()->isEmpty(),
            'Request failed: '.$this->client->getResponse()->getStatusCode()
        );
        $this->assertEquals(Booklet::DISTRIBUTED, $doctrine->getRepository(Booklet::class)->find(['id' => $booklet->getId()])->getStatus());
    }

    public function testAssignToCommunity()
    {
        $doctrine = self::$container->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $result = $em->createQueryBuilder()
                ->select('c.id AS communityId')
                ->addSelect('a.id AS assistanceId')
                ->from(Community::class, 'c')
                ->join('c.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('There needs to be at least one community assigned to an assistance to complete this test');
            return;
        }

        $booklet = $doctrine->getRepository(Booklet::class)->findBy(['status' => Booklet::UNASSIGNED], ['id' => 'asc'])[0];

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$result['assistanceId'].'/communities/'.$result['communityId'].'/booklets/'.$booklet->getCode(), [], [], $this->addAuth());

        $this->assertTrue(
            $this->client->getResponse()->isEmpty(),
            'Request failed: '.$this->client->getResponse()->getStatusCode()
        );
        $this->assertEquals(Booklet::DISTRIBUTED, $doctrine->getRepository(Booklet::class)->find(['id' => $booklet->getId()])->getStatus());
    }

    public function testAssignToInstitution()
    {
        $doctrine = self::$container->get('doctrine');

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $result = $em->createQueryBuilder()
                ->select('i.id AS institutionId')
                ->addSelect('a.id AS assistanceId')
                ->from(Institution::class, 'i')
                ->join('i.assistanceBeneficiary', 'ab')
                ->join('ab.assistance', 'a')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('There needs to be at least one institution assigned to an assistance to complete this test');
            return;
        }

        $booklet = $doctrine->getRepository(Booklet::class)->findBy(['status' => Booklet::UNASSIGNED], ['id' => 'asc'])[0];

        $this->client->request('PUT', '/api/basic/web-app/v1/assistances/'.$result['assistanceId'].'/institutions/'.$result['institutionId'].'/booklets/'.$booklet->getCode(), [], [], $this->addAuth());

        $this->assertTrue(
            $this->client->getResponse()->isEmpty(),
            'Request failed: '.$this->client->getResponse()->getStatusCode()
        );
        $this->assertEquals(Booklet::DISTRIBUTED, $doctrine->getRepository(Booklet::class)->find(['id' => $booklet->getId()])->getStatus());
    }
}
