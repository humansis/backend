<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use DistributionBundle\Entity\Modality;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;

class ModalityControllerTest extends AbstractFunctionalApiTest
{
    /**
     * @throws Exception
     */
    public function testGetModalities()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/modalities', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment(
            '{"totalCount": "*", "data": [{"code": "*", "value": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModalityTypes()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $modality = $em->getRepository(Modality::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/modalities/'.$modality->getName().'/types', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment(
            '{"totalCount": "*", "data": [{"code": "*", "value": "*"}]}',
            $this->client->getResponse()->getContent()
        );
    }
}
