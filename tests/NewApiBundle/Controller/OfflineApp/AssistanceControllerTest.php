<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class AssistanceControllerTest extends AbstractFunctionalApiTest
{
    public function testAsisstancesByProject()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $projectId = $em->createQueryBuilder()
                ->select('p.id')
                ->from(\DistributionBundle\Entity\Assistance::class, 'a')
                ->join('a.project', 'p')
                ->andWhere('a.validated = 1')
                ->andWhere('a.archived = 0')
                ->andWhere('p.iso3 = :iso3')
                ->setParameter('iso3', 'KHM')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
            $this->markTestSkipped('You need to have at least one project with assistance in database to complete this test.');
            return;
        }

        $this->client->request('GET', '/api/basic/offline-app/v2/projects/'.$projectId.'/assistances', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('[{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "expirationDate": "*",
            "type": "*",
            "target": "*",
            "projectId": "*",
            "locationId": "*",
            "commodityIds": ["*"],
            "description": "*",
            "validated": "*",
            "remote": "*",
            "foodLimit": "*",
            "nonfoodLimit": "*",
            "cashbackLimit": "*",
            "completed": "*",
            "numberOfBeneficiaries": "*"
         }]', $this->client->getResponse()->getContent());
    }
}
