<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller;

use CommonBundle\Entity\Adm1;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Vendor;

class VendorControllerTest extends AbstractFunctionalApiTest
{
    private $vendorUsername;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->vendorUsername = time().'-testvendor@example.org';
    }

    /**
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testCreate()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $adm1Results = $em->getRepository(Adm1::class)->findAll();

        if (empty($adm1Results)) {
            $this->markTestSkipped('To perform VendorController CRUD tests, you need to have at least one Adm1 record in database.');
        }

        /** @var User[] $users */
        $users = $em->getRepository(User::class)->findBy(['vendor' => null], ['id' => 'asc']);

        if (empty($users)) {
            $this->markTestSkipped('There needs to be at least one user in system which is not assigned to any vendor to complete this test');
        }

        $this->client->request('POST', '/api/basic/web-app/v1/vendors', $data = [
            'shop' => 'test shop',
            'name' => $this->vendorUsername,
            'addressStreet' => 'test street',
            'addressNumber' => '1234566',
            'addressPostcode' => '039 98',
            'locationId' => $adm1Results[0]->getId(),
            'userId' => $users[0]->getId(),
            'vendorNo' => 'v-10',
            'contractNo' => 'c-10',
            'canSellFood' => false,
            'canSellNonFood' => false,
            'canSellCashback' => false,
            'canDoRemoteDistributions' => true,
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed (status code '.$this->client->getResponse()->getStatusCode().'): '.$this->client->getResponse()->getContent()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('shop', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('addressStreet', $result);
        $this->assertArrayHasKey('addressNumber', $result);
        $this->assertArrayHasKey('addressPostcode', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
        $this->assertArrayHasKey('adm2Id', $result);
        $this->assertArrayHasKey('adm3Id', $result);
        $this->assertArrayHasKey('adm4Id', $result);
        $this->assertArrayHasKey('vendorNo', $result);
        $this->assertArrayHasKey('contractNo', $result);
        $this->assertArrayHasKey('canSellFood', $result);
        $this->assertArrayHasKey('canSellNonFood', $result);
        $this->assertArrayHasKey('canSellCashback', $result);
        $this->assertArrayHasKey('canDoRemoteDistributions', $result);

        $this->assertEquals($data['shop'], $result['shop']);
        $this->assertEquals($data['addressPostcode'], $result['addressPostcode']);
        $this->assertEquals($data['vendorNo'], $result['vendorNo']);
        $this->assertEquals($data['contractNo'], $result['contractNo']);
        $this->assertEquals($data['canSellFood'], $result['canSellFood']);
        $this->assertEquals($data['canSellNonFood'], $result['canSellNonFood']);
        $this->assertEquals($data['canSellCashback'], $result['canSellCashback']);

        return $result;
    }

    /**
     * @depends testCreate
     *
     * @param array $vendor
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testUpdate(array $vendor)
    {
        $this->client->request('PUT', '/api/basic/web-app/v1/vendors/'.$vendor['id'], $data = [
            'shop' => 'edited',
            'name' => $this->vendorUsername,
            'addressStreet' => $vendor['addressStreet'],
            'addressNumber' => $vendor['addressNumber'],
            'addressPostcode' => '0000',
            'locationId' => $vendor['locationId'],
            'vendorNo' => 'v-10-changed',
            'contractNo' => 'c-10-changed',
            'canSellFood' => true,
            'canSellNonFood' => true,
            'canSellCashback' => true,
            'canDoRemoteDistributions' => false,
        ], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('shop', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('addressStreet', $result);
        $this->assertArrayHasKey('addressNumber', $result);
        $this->assertArrayHasKey('addressPostcode', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
        $this->assertArrayHasKey('adm2Id', $result);
        $this->assertArrayHasKey('adm3Id', $result);
        $this->assertArrayHasKey('adm4Id', $result);
        $this->assertArrayHasKey('vendorNo', $result);
        $this->assertArrayHasKey('contractNo', $result);
        $this->assertArrayHasKey('canSellFood', $result);
        $this->assertArrayHasKey('canSellNonFood', $result);
        $this->assertArrayHasKey('canSellCashback', $result);
        $this->assertArrayHasKey('canDoRemoteDistributions', $result);

        $this->assertEquals($data['shop'], $result['shop']);
        $this->assertEquals($data['addressPostcode'], $result['addressPostcode']);
        $this->assertEquals($data['vendorNo'], $result['vendorNo']);
        $this->assertEquals($data['contractNo'], $result['contractNo']);
        $this->assertEquals($data['canSellFood'], $result['canSellFood']);
        $this->assertEquals($data['canSellNonFood'], $result['canSellNonFood']);
        $this->assertEquals($data['canSellCashback'], $result['canSellCashback']);
        $this->assertEquals($data['canDoRemoteDistributions'], $result['canDoRemoteDistributions']);

        return $result['id'];
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGet(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/vendors/'.$id, [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('shop', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('addressStreet', $result);
        $this->assertArrayHasKey('addressNumber', $result);
        $this->assertArrayHasKey('addressPostcode', $result);
        $this->assertArrayHasKey('locationId', $result);
        $this->assertArrayHasKey('adm1Id', $result);
        $this->assertArrayHasKey('adm2Id', $result);
        $this->assertArrayHasKey('adm3Id', $result);
        $this->assertArrayHasKey('adm4Id', $result);
        $this->assertArrayHasKey('vendorNo', $result);
        $this->assertArrayHasKey('contractNo', $result);
        $this->assertArrayHasKey('canDoRemoteDistributions', $result);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testList()
    {
        $this->client->request('GET', '/api/basic/web-app/v1/vendors?filter[id][]=1&sort[]=name.asc', [], [], $this->addAuth());

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testSummaries()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $vendor = $em->getRepository(Vendor::class)->findBy([], ['id' => 'asc'])[0];

        $this->client->request('GET', '/api/basic/web-app/v1/vendors/'.$vendor->getId().'/summaries', [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());
        $this->assertJsonFragment('{
            "redeemedSmartcardPurchasesTotalCount": "*",
            "redeemedSmartcardPurchasesTotalValue": "*"
        }', $this->client->getResponse()->getContent());
    }

    /**
     * @depends testGet
     *
     * @param int $id
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testDelete(int $id)
    {
        $this->client->request('DELETE', '/api/basic/web-app/v1/vendors/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isEmpty());

        return $id;
    }

    /**
     * @depends testDelete
     *
     * @param int $id
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testGetNotExists(int $id)
    {
        $this->client->request('GET', '/api/basic/web-app/v1/vendors/'.$id, [], [], $this->addAuth());

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }
}
