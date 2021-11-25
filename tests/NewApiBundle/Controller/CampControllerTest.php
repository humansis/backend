<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Camp;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class CampControllerTest extends AbstractFunctionalApiTest
{
    public function testCamps()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/camps', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": "*"}', $this->client->getResponse()->getContent()
        );
    }

    public function testCamp()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $campId = $em->createQueryBuilder()
                ->select('c.id')
                ->from(Camp::class, 'c')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one camp with location in system');
            return;
        }

        $this->client->request('GET', '/api/basic/web-app/v1/camps/'.$campId, [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "id": '.$campId.',
            "name": "*",
            "locationId": "*",
            "adm1Id": "*",
            "adm2Id": "*",
            "adm3Id": "*",
            "adm4Id": "*"
        }', $this->client->getResponse()->getContent()
        );
    }

    public function testCampsByLocation()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        try {
            $locationId = $em->createQueryBuilder()
                ->select('l.id')
                ->from(Camp::class, 'c')
                ->leftJoin('c.location', 'l')
                ->andWhere('l.id IS NOT NULL')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one camp with location in system');
            return;
        }

        $this->client->request('GET', "/api/basic/web-app/v1/locations/$locationId/camps", [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "name": "*",
                    "locationId": "*",
                    "adm1Id": "*",
                    "adm2Id": "*",
                    "adm3Id": "*",
                    "adm4Id": "*"
                }
            ]}', $this->client->getResponse()->getContent()
        );
    }

}
