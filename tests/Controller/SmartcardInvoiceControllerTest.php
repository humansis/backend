<?php

namespace Tests\Controller;

use Component\Assistance\Domain\Assistance as AssistanceDomain;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\AssistanceBeneficiary;
use Entity\Location;
use Entity\Vendor;
use Enum\ModalityType;
use Exception;
use Tests\BMSServiceTestCase;
use Tests\ComponentHelper\AssistanceHelper;
use Tests\ComponentHelper\BeneficiaryHelper;
use Tests\ComponentHelper\DepositHelper;
use Tests\ComponentHelper\ProjectHelper;
use Tests\ComponentHelper\SmartcardInvoiceHelper;
use Tests\ComponentHelper\SmartcardPurchaseHelper;
use Tests\ComponentHelper\VendorHelper;

class SmartcardInvoiceControllerTest extends BMSServiceTestCase
{
    use ProjectHelper;
    use BeneficiaryHelper;
    use AssistanceHelper;
    use SmartcardPurchaseHelper;
    use VendorHelper;
    use DepositHelper;
    use SmartcardInvoiceHelper;

    /**
     * @var Location
     */
    private $location;

    /**
     * @var Vendor
     */
    private $vendor;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
        $this->location = $this->em->getRepository(Location::class)->findOneBy(['countryIso3' => 'SYR']);

        $this->em->beginTransaction();
        $this->vendor = $this->createVendor(
            self::buildVendorInputType(
                $this->location->getId(),
                $this->getTestUser('Vendor for testing ' . time())->getId()
            )
        );
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    /**
     * @return AssistanceDomain
     * @throws Exception
     */
    private function createSmartcardAssistance(): AssistanceDomain
    {
        $project = $this->createProject($this->getTestUser(), self::getCreateInputType('SYR'));

        $this->createHousehold(
            self::buildHouseholdInputType(
                [$project->getId()],
                self::buildResidencyAddressInputType($this->location->getId()),
                [
                    self::buildBeneficiaryInputType(
                        true,
                        1,
                        self::generateNationalId(),
                        self::generatePhoneInputType()
                    ),
                ]
            ),
            'SYR'
        );
        $this->em->flush();

        return $this->createAssistance(
            self::buildAssistanceInputType(
                $project,
                $this->location,
                [self::buildCommoditiesType('USD', ModalityType::SMART_CARD, 100)],
                [self::buildSelectionCriteriaInputType()]
            )
        );
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testPurchaseIsNotRedeemableBeforeSync(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';

        $purchase = $this->createPurchase(
            'AAAAA11111',
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->request(
            'GET',
            '/api/basic/web-app/v1/vendors/' . $this->vendor->getId() . '/smartcard-redemption-candidates'
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $response = $this->client->getResponse()->getContent();

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {"purchaseIds": "*", "projectId": "*", "value": "*", "currency": "*", "canRedeem": "*"}
            ]
        }',
            $response
        );

        $responseArray = json_decode($response, true);
        $data = $responseArray['data'][0];
        $this->assertEquals($assistanceDomain->getAssistanceRoot()->getProject()->getId(), $data['projectId']);
        $this->assertEquals($purchase->getId(), $data['purchaseIds'][0]);
        $this->assertEquals((float) ($purchaseValue), (float) $data['value']);
        $this->assertEquals($purchaseCurrency, $data['currency']);
        $this->assertEquals(false, $data['canRedeem']);
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testRedeemablePurchaseAfterSync(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';
        $smartcardNumber = 'AAAAA11111';

        /**
         * @var AssistanceBeneficiary $assistanceBeneficiary
         */
        $assistanceBeneficiary = $assistanceDomain->getBeneficiaries()[0];
        $reliefPackages = $assistanceBeneficiary->getReliefPackages();
        if ($reliefPackages->count() > 1) {
            $this->fail('There should not be more Relief Packages');
        }

        $purchase = $this->createPurchase(
            $smartcardNumber,
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->createDeposit(
            $smartcardNumber,
            self::buildDepositInputType($reliefPackages->first()->getId(), $purchaseValue),
            $this->getTestUser()
        );
        $this->em->flush();

        $this->request(
            'GET',
            '/api/basic/web-app/v1/vendors/' . $this->vendor->getId() . '/smartcard-redemption-candidates'
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $response = $this->client->getResponse()->getContent();

        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {"purchaseIds": "*", "projectId": "*", "value": "*", "currency": "*", "canRedeem": "*"}
            ]
        }',
            $response
        );

        $responseArray = json_decode($response, true);
        $data = $responseArray['data'][0];
        $this->assertEquals($assistanceDomain->getAssistanceRoot()->getProject()->getId(), $data['projectId']);
        $this->assertEquals($purchase->getId(), $data['purchaseIds'][0]);
        $this->assertEquals((float) ($purchaseValue), (float) $data['value']);
        $this->assertEquals($purchaseCurrency, $data['currency']);
        $this->assertEquals(true, $data['canRedeem']);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testPurchaseCanNotBeInvoicedBeforeSync(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';
        $purchase = $this->createPurchase(
            'AAAAA11111',
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->request(
            'POST',
            '/api/basic/web-app/v1/vendors/' . $this->vendor->getId() . '/smartcard-redemption-batches',
            [
                'purchaseIds' => [$purchase->getId()],
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isClientError(),
            'Request must return client error because of not synced deposit: ' . $this->client->getResponse(
            )->getContent()
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCanBeInvoicedAfterSync(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';
        $smartcardNumber = 'AAAAA11111';

        /**
         * @var AssistanceBeneficiary $assistanceBeneficiary
         */
        $assistanceBeneficiary = $assistanceDomain->getBeneficiaries()[0];
        $reliefPackages = $assistanceBeneficiary->getReliefPackages();
        if ($reliefPackages->count() > 1) {
            $this->fail('There should not be more Relief Packages');
        }

        $purchase = $this->createPurchase(
            $smartcardNumber,
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->createDeposit(
            $smartcardNumber,
            self::buildDepositInputType($reliefPackages->first()->getId(), $purchaseValue),
            $this->getTestUser()
        );
        $this->em->flush();

        $this->request(
            'POST',
            '/api/basic/web-app/v1/vendors/' . $this->vendor->getId() . '/smartcard-redemption-batches',
            [
                'purchaseIds' => [$purchase->getId()],
            ]
        );

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment(
            '{
            "projectId": "*",
            "value": "*",
            "currency": "*",
            "date": "*"
        }',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testInvoicesByVendor(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';
        $smartcardNumber = 'AAAAA11111';

        /**
         * @var AssistanceBeneficiary $assistanceBeneficiary
         */
        $assistanceBeneficiary = $assistanceDomain->getBeneficiaries()[0];
        $reliefPackages = $assistanceBeneficiary->getReliefPackages();
        if ($reliefPackages->count() > 1) {
            $this->fail('There should not be more Relief Packages');
        }

        $purchase = $this->createPurchase(
            $smartcardNumber,
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->createDeposit(
            $smartcardNumber,
            self::buildDepositInputType($reliefPackages->first()->getId(), $purchaseValue),
            $this->getTestUser()
        );
        $this->em->flush();

        $invoice = $this->createInvoice(
            $this->vendor,
            self::buildInvoiceCreateInputType([$purchase->getId()]),
            $this->getTestUser()
        );

        $this->request(
            'GET',
            '/api/basic/web-app/v1/vendors/' . $this->vendor->getId() . '/smartcard-redemption-batches'
        );

        $content = $this->client->getResponse()->getContent();

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $content
        );
        $this->assertJsonFragment(
            '{
            "totalCount": "*",
            "data": [
                {"id": "*", "projectId": "*", "contractNumber": "*", "value": "*", "currency": "*", "quantity": "*", "date": "*"}
            ]
        }',
            $content
        );

        $data = json_decode($content, true)['data'][0];
        $this->assertEquals($invoice->getId(), $data['id']);
        $this->assertEquals($assistanceDomain->getAssistanceRoot()->getProject()->getId(), $data['projectId']);
        $this->assertEquals($purchaseValue, $data['value']);
        $this->assertEquals($purchaseCurrency, $data['currency']);
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testPurchaseIsRedeemableForVendorApp(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';
        $smartcardNumber = 'AAAAA11111';

        /**
         * @var AssistanceBeneficiary $assistanceBeneficiary
         */
        $assistanceBeneficiary = $assistanceDomain->getBeneficiaries()[0];
        $reliefPackages = $assistanceBeneficiary->getReliefPackages();
        if ($reliefPackages->count() > 1) {
            $this->fail('There should not be more Relief Packages');
        }

        $this->createPurchase(
            $smartcardNumber,
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->createDeposit(
            $smartcardNumber,
            self::buildDepositInputType($reliefPackages->first()->getId(), $purchaseValue),
            $this->getTestUser()
        );
        $this->em->flush();

        $this->request(
            'GET',
            '/api/basic/vendor-app/v3/vendors/' . $this->vendor->getId() . '/smartcard-redemption-candidates'
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $response = $this->client->getResponse()->getContent();

        $this->assertJsonFragment('[{"projectId":"*","value":"*","currency":"*"}]', $response);

        $data = json_decode($response, true);
        $this->assertEquals($assistanceDomain->getAssistanceRoot()->getProject()->getId(), $data[0]['projectId']);
        $this->assertEquals((float) ($purchaseValue), (float) $data[0]['value']);
        $this->assertEquals($purchaseCurrency, $data[0]['currency']);
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function testPurchaseIsNotRedeemableForVendorApp(): void
    {
        $assistanceDomain = $this->createSmartcardAssistance();
        if ($assistanceDomain->getBeneficiaries()->count() === 0) {
            $this->fail('There is no Beneficiary in Assistance.');
        }

        $purchaseValue = 60;
        $purchaseCurrency = 'USD';
        $smartcardNumber = 'AAAAA11111';

        /**
         * @var AssistanceBeneficiary $assistanceBeneficiary
         */
        $assistanceBeneficiary = $assistanceDomain->getBeneficiaries()[0];
        $reliefPackages = $assistanceBeneficiary->getReliefPackages();
        if ($reliefPackages->count() > 1) {
            $this->fail('There should not be more Relief Packages');
        }

        $this->createPurchase(
            $smartcardNumber,
            self::buildSmartcardPurchaseInputType(
                $assistanceDomain->getAssistanceRoot()->getId(),
                $assistanceDomain->getBeneficiaries()[0]->getBeneficiary()->getId(),
                $this->vendor->getId(),
                self::buildPurchaseProductInputType($purchaseCurrency, $purchaseValue)
            )
        );

        $this->request(
            'GET',
            '/api/basic/vendor-app/v3/vendors/' . $this->vendor->getId() . '/smartcard-redemption-candidates'
        );
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: ' . $this->client->getResponse()->getContent()
        );

        $response = $this->client->getResponse()->getContent();

        $this->assertJson(json_encode([]), $response);
    }
}
