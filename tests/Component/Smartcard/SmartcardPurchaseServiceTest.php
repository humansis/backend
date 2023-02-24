<?php

declare(strict_types=1);

namespace Tests\Component\Smartcard;

use Component\Smartcard\Exception\SmartcardPurchaseAlreadyProcessedException;
use Component\Smartcard\SmartcardPurchaseService;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityNotFoundException;
use InputType\PurchaseProductInputType;
use InputType\SmartcardPurchaseInputType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SmartcardPurchaseServiceTest extends KernelTestCase
{
    private readonly SmartcardPurchaseService $smartcardPurchaseService;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static::bootKernel();

        $container = self::getContainer();
        $this->smartcardPurchaseService = $container->get(SmartcardPurchaseService::class);
    }

    /**
     * @dataProvider provideNonValidPurchases
     */
    public function testPurchaseExceptionStates(
        string $serialNumber,
        SmartcardPurchaseInputType $input,
        string $expectedException
    ): void {
        $this->expectException($expectedException);

        $this->smartcardPurchaseService->purchase($serialNumber, $input);
    }

    public function testValidPurchase(): void
    {
        $serialNumber = '04552032216C85';
        $input = $this->createSmartcardPurchaseInputType([
            'vendorId' => 1,
            'createdAt' => DateTime::createFromFormat(DateTimeInterface::ISO8601, '2010-02-12T15:19:21+00:00'),
            'beneficiaryId' => 2,
            'assistanceId' => 1,
            'products' => [
                [
                    'currency' => 'USD',
                    'id' => 1,
                    'value' => 14,
                ],
                [
                    'currency' => 'USD',
                    'id' => 2,
                    'value' => 10,
                ],
            ],
        ]);

        $smartcardPurchase = $this->smartcardPurchaseService->purchase($serialNumber, $input);

        $this->assertEquals('USD', $smartcardPurchase->getCurrency());
        $this->assertEquals(1, $smartcardPurchase->getAssistance()->getId());
        $this->assertEquals(1, $smartcardPurchase->getVendor()->getId());
        $this->assertEquals(24.0, $smartcardPurchase->getRecordsValue());
        $this->assertEquals(2, count($smartcardPurchase->getRecords()));
    }

    /**
     * @depends testValidPurchase
     */
    public function testDuplicityPurchase(): void
    {
        $serialNumber = '04552032216C85';
        $input = $this->createSmartcardPurchaseInputType([
            'vendorId' => 1,
            'createdAt' => DateTime::createFromFormat(DateTimeInterface::ISO8601, '2010-02-12T15:19:21+00:00'),
            'beneficiaryId' => 2,
            'assistanceId' => 1,
            'products' => [
                [
                    'currency' => 'USD',
                    'id' => 1,
                    'value' => 14,
                ],
            ],
        ]);

        $this->expectException(SmartcardPurchaseAlreadyProcessedException::class);
        $this->smartcardPurchaseService->purchase($serialNumber, $input);
    }

    private function provideNonValidPurchases(): array
    {
        return [
            'Non existent beneficiary' => [
                '04552032216C85',
                $this->createSmartcardPurchaseInputType([
                    'vendorId' => 1,
                    'createdAt' => DateTime::createFromFormat(DateTimeInterface::ISO8601, '2007-02-12T15:19:21+00:00'),
                    'beneficiaryId' => 5364565,
                    'assistanceId' => 1,
                    'products' => [
                        [
                            'currency' => 'USD',
                            'id' => 1,
                            'value' => 14,
                        ],
                    ],
                ]),
                EntityNotFoundException::class,
            ],
            'Non existent vendor' => [
                '04552032216C85',
                $this->createSmartcardPurchaseInputType([
                    'vendorId' => 5364565,
                    'createdAt' => DateTime::createFromFormat(DateTimeInterface::ISO8601, '2006-02-12T15:19:21+00:00'),
                    'beneficiaryId' => 2,
                    'assistanceId' => 1,
                    'products' => [
                        [
                            'currency' => 'USD',
                            'id' => 1,
                            'value' => 14,
                        ],
                    ],
                ]),
                EntityNotFoundException::class,
            ],
            'Non existent assistance' => [
                '04552032216C85',
                $this->createSmartcardPurchaseInputType([
                    'vendorId' => 1,
                    'createdAt' => DateTime::createFromFormat(DateTimeInterface::ISO8601, '2005-02-12T15:19:21+00:00'),
                    'beneficiaryId' => 2,
                    'assistanceId' => 5364565,
                    'products' => [
                        [
                            'currency' => 'USD',
                            'id' => 1,
                            'value' => 14,
                        ],
                    ],
                ]),
                EntityNotFoundException::class,
            ],
            'Non existent product' => [
                '04552032216C85',
                $this->createSmartcardPurchaseInputType([
                    'vendorId' => 1,
                    'createdAt' => DateTime::createFromFormat(DateTimeInterface::ISO8601, '2004-02-12T15:19:21+00:00'),
                    'beneficiaryId' => 2,
                    'assistanceId' => 1,
                    'products' => [
                        [
                            'currency' => 'USD',
                            'id' => 5364565,
                            'value' => 14,
                        ],
                    ],
                ]),
                EntityNotFoundException::class,
            ],
        ];
    }

    private function createSmartcardPurchaseInputType(array $data): SmartcardPurchaseInputType
    {
        $smartcardPurchaseInputType = new SmartcardPurchaseInputType();

        $smartcardPurchaseInputType->setVendorId($data['vendorId']);
        $smartcardPurchaseInputType->setCreatedAt($data['createdAt']);
        $smartcardPurchaseInputType->setBeneficiaryId($data['beneficiaryId']);
        $smartcardPurchaseInputType->setAssistanceId($data['assistanceId']);

        foreach ($data['products'] as $product) {
            $smartcardPurchaseInputType->addProduct($this->getPurchaseProductInputType($product));
        }

        return $smartcardPurchaseInputType;
    }

    private function getPurchaseProductInputType(array $data): PurchaseProductInputType
    {
        $purchaseProductInputType = new PurchaseProductInputType();
        $purchaseProductInputType->setId($data['id']);
        $purchaseProductInputType->setQuantity($data['quantity'] ?? 1);
        $purchaseProductInputType->setValue($data['value']);
        $purchaseProductInputType->setCurrency($data['currency']);

        return $purchaseProductInputType;
    }
}
