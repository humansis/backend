<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\WebApp\Smartcards;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\Vendor;

class AnalyticsControllerTest extends AbstractFunctionalApiTest
{

    public function testBeneficiaryAnalytics()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $beneficiaryId = $em->getRepository(Beneficiary::class)->findOneBy([], ['id'=>'asc'])->getId();

        $this->client->request('GET', '/api/basic/web-app/v1/smartcard/analytics/beneficiary/'.$beneficiaryId, [], [], $this->addAuth());
        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSmartcardAnalytics()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $smartcardId = $em->getRepository(Smartcard::class)->findOneBy([], ['id'=>'asc'])->getId();

        $this->client->request('GET', '/api/basic/web-app/v1/smartcard/analytics/smartcard/'.$smartcardId, [], [], $this->addAuth());
        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSmartcardsAnalytics()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $smartcardSerialNumber = $em->getRepository(Smartcard::class)->findOneBy([], ['id'=>'asc'])->getSerialNumber();

        $this->client->request('GET', '/api/basic/web-app/v1/smartcard/analytics/smartcards/'.$smartcardSerialNumber, [], [], $this->addAuth());
        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testVendorAnalytics()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();
        $vendorId = $em->getRepository(Vendor::class)->findOneBy([], ['id'=>'asc'])->getId();

        $this->client->request('GET', '/api/basic/web-app/v1/smartcard/analytics/vendor/'.$vendorId, [], [], $this->addAuth());
        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
