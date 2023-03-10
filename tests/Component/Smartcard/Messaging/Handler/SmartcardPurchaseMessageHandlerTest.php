<?php

declare(strict_types=1);

namespace Tests\Component\Smartcard\Messaging\Handler;

use Component\Smartcard\Exception\SmartcardPurchaseRequestValidationErrorException;
use Component\Smartcard\Messaging\Handler\SmartcardPurchaseMessageHandler;
use Component\Smartcard\Messaging\Message\SmartcardPurchaseMessage;
use Component\Smartcard\SmartcardPurchaseService;
use PHPUnit\Framework\MockObject\MockObject;
use Serializer\InputTypeObjectSerializerFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SmartcardPurchaseMessageHandlerTest extends KernelTestCase
{
    private readonly SmartcardPurchaseMessageHandler $smartcardPurchaseMessageHandler;

    private readonly MockObject $smartcardPurchaseServiceMock;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static::bootKernel();

        $container = self::getContainer();

        $validator = $container->get(ValidatorInterface::class);
        $serializer = InputTypeObjectSerializerFactory::createSerializer();

        $this->smartcardPurchaseServiceMock = $this->getMockBuilder(SmartcardPurchaseService::class)
            ->onlyMethods(['purchase'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->smartcardPurchaseMessageHandler = new SmartcardPurchaseMessageHandler(
            $validator,
            $this->smartcardPurchaseServiceMock,
            $serializer
        );
    }

    /**
     * @dataProvider provideNonValidMessages
     */
    public function testInvalidPurchaseMessages(
        SmartcardPurchaseMessage $message
    ): void {
        $this->expectException(SmartcardPurchaseRequestValidationErrorException::class);

        $this->smartcardPurchaseServiceMock->expects($this->never())
            ->method('purchase')
            ->withAnyParameters();

        ($this->smartcardPurchaseMessageHandler)($message);
    }

    public function testProcessingValidPurchaseMessage(): void
    {
        $message = new SmartcardPurchaseMessage(
            '04552032216C85',
            [
                'assistanceId' => 1,
                'beneficiaryId' => 1,
                'createdAt' => '2005-05-05T16:59:13+0300',
                'vendorId' => 1,
                'products' => [
                    [
                        'currency' => 'USD',
                        'id' => 1,
                        'value' => 50,
                    ],
                ],
            ]
        );

        $this->smartcardPurchaseServiceMock->expects($this->once())
            ->method('purchase')
            ->withAnyParameters();

        ($this->smartcardPurchaseMessageHandler)($message);
    }

    private function provideNonValidMessages(): array
    {
        return [
            'Missing assistance ID' => [
                new SmartcardPurchaseMessage(
                    '04552032216C85',
                    [
                        'beneficiaryId' => 1,
                        'createdAt' => '2005-05-05T16:59:13+0300',
                        'vendorId' => 1,
                        'products' => [
                            [
                                'currency' => 'USD',
                                'id' => 1,
                                'value' => 50,
                            ],
                        ],
                    ]
                ),
            ],
            'Missing beneficiary ID' => [
                new SmartcardPurchaseMessage(
                    '04552032216C85',
                    [
                        'assistanceId' => 1,
                        'createdAt' => '2005-05-05T16:59:13+0300',
                        'vendorId' => 1,
                        'products' => [
                            [
                                'currency' => 'USD',
                                'id' => 1,
                                'value' => 50,
                            ],
                        ],
                    ]
                ),
            ],
            'Missing vendor ID' => [
                new SmartcardPurchaseMessage(
                    '04552032216C85',
                    [
                        'assistanceId' => 1,
                        'beneficiaryId' => 1,
                        'createdAt' => '2005-05-05T16:59:13+0300',
                        'products' => [
                            [
                                'currency' => 'USD',
                                'id' => 1,
                                'value' => 50,
                            ],
                        ],
                    ]
                ),
            ],
            'Missing products' => [
                new SmartcardPurchaseMessage(
                    '04552032216C85',
                    [
                        'assistanceId' => 1,
                        'beneficiaryId' => 1,
                        'createdAt' => '2005-05-05T16:59:13+0300',
                        'vendorId' => 1,
                    ]
                ),
            ],
            'Invalid createdAt value' => [
                new SmartcardPurchaseMessage(
                    '04552032216C85',
                    [
                        'assistanceId' => 1,
                        'beneficiaryId' => 1,
                        'createdAt' => 'diasjdia',
                        'vendorId' => 1,
                        'products' => [
                            [
                                'currency' => 'USD',
                                'id' => 1,
                                'value' => 50,
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }
}
