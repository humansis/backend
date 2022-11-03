<?php

namespace Tests\Controller\OfflineApp;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Entity\Assistance;
use Exception;
use Entity\Project;
use Tests\BMSServiceTestCase;

class AssistanceControllerTest extends BMSServiceTestCase
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
        $this->client = self::getContainer()->get('test.client');
    }

    public function testAsisstancesByProject()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        try {
            $projectId = $em->createQueryBuilder()
                ->select('p.id')
                ->from(Assistance::class, 'a')
                ->join('a.project', 'p')
                ->andWhere('a.validatedBy IS NOT NULL')
                ->andWhere('a.archived = 0')
                ->andWhere('p.countryIso3 = :iso3')
                ->setParameter('iso3', 'KHM')
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleScalarResult();
        } catch (NoResultException) {
            $this->markTestSkipped(
                'You need to have at least one project with assistance in database to complete this test.'
            );

            return;
        }

        $this->request('GET', '/api/basic/offline-app/v2/projects/' . $projectId . '/distributions');

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );
        $this->assertJsonFragment(
            '[{
            "id": "*",
            "name": "*",
            "dateDistribution": "*",
            "dateExpiration": "*",
            "targetType": "*",
            "commodities": ["*"],
            "beneficiariesCount": "*",
            "foodLimit": "*",
            "nonfoodLimit": "*",
            "cashbackLimit": "*",
            "remoteDistributionAllowed": "*",
            "completed": "*",
            "validated": "*",
            "archived": "*"
         }]',
            $this->client->getResponse()->getContent()
        );
    }
}
