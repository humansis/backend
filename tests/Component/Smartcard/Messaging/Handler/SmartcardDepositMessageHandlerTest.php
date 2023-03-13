<?php

declare(strict_types=1);

namespace Tests\Component\Smartcard\Messaging\Handler;

use Component\Smartcard\Exception\SmartcardDepositRequestValidationErrorException;
use Component\Smartcard\Messaging\Handler\SmartcardDepositMessageHandler;
use Component\Smartcard\Messaging\Message\SmartcardDepositMessage;
use Component\Smartcard\SmartcardDepositService;
use InputType\Smartcard\DepositInputType;
use InputType\SynchronizationBatch\CreateDepositInputType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SmartcardDepositMessageHandlerTest extends KernelTestCase
{
    private readonly SmartcardDepositMessageHandler $smartcardDepositMessageHandler;

    private readonly MockObject $smartcardDepositServiceMock;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static::bootKernel();

        $container = self::getContainer();

        $validator = $container->get(ValidatorInterface::class);

        $this->smartcardDepositServiceMock = $this->getMockBuilder(SmartcardDepositService::class)
            ->onlyMethods(['processDeposit'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->smartcardDepositMessageHandler = new SmartcardDepositMessageHandler(
            $validator,
            $this->smartcardDepositServiceMock
        );
    }

    /**
     * @dataProvider provideNonValidMessages
     */
    public function testInvalidDepositMessages(
        SmartcardDepositMessage $message
    ): void {
        $this->expectException(SmartcardDepositRequestValidationErrorException::class);

        $this->smartcardDepositServiceMock->expects($this->never())
            ->method('processDeposit')
            ->withAnyParameters();

        ($this->smartcardDepositMessageHandler)($message);
    }

    /**
     * @dataProvider provideValidMessages
     */
    public function testProcessingValidDepositMessage(
        SmartcardDepositMessage $message
    ): void {
        $this->smartcardDepositServiceMock->expects($this->once())
            ->method('processDeposit')
            ->withAnyParameters();

        ($this->smartcardDepositMessageHandler)($message);
    }

    private function provideNonValidMessages(): array
    {
        return [
            'DepositInputType Empty request body' => [
                new SmartcardDepositMessage(
                    1,
                    DepositInputType::class,
                    '04552032216C85',
                    []
                ),
            ],
            'DepositInputType Invalid createdAt' => [
                new SmartcardDepositMessage(
                    1,
                    DepositInputType::class,
                    '04552032216C85',
                    [
                        'reliefPackageId' => 1,
                        'createdAt' => 5,
                    ]
                ),
            ],
            'DepositInputType Missing reliefPackageId' => [
                new SmartcardDepositMessage(
                    1,
                    DepositInputType::class,
                    '04552032216C85',
                    [
                        'createdAt' => "2020-12-24T00:00:00.000Z",
                    ]
                ),
            ],
            'CreateDepositInputType Missing reliefPackageId' => [
                new SmartcardDepositMessage(
                    1,
                    CreateDepositInputType::class,
                    '04552032216C85',
                    [
                        'smartcardSerialNumber' => '04552032216C85',
                        'balanceBefore' => 1,
                        'balanceAfter' => 1,
                        'createdAt' => "2020-12-24T00:00:00.000Z",
                    ]
                ),
            ],
            'CreateDepositInputType Missing createdAt' => [
                new SmartcardDepositMessage(
                    1,
                    CreateDepositInputType::class,
                    '04552032216C85',
                    [
                        'smartcardSerialNumber' => '04552032216C85',
                        'reliefPackageId' => 1,
                        'balanceBefore' => 1,
                        'balanceAfter' => 1,
                    ]
                ),
            ],
            'CreateDepositInputType Empty request body' => [
                new SmartcardDepositMessage(
                    1,
                    CreateDepositInputType::class,
                    '04552032216C85',
                    []
                ),
            ],
            'CreateDepositInputType Invalid createdAt' => [
                new SmartcardDepositMessage(
                    1,
                    CreateDepositInputType::class,
                    '04552032216C85',
                    [
                        'smartcardSerialNumber' => '04552032216C85',
                        'reliefPackageId' => 1,
                        'balanceBefore' => 1,
                        'balanceAfter' => 1,
                        'createdAt' => 50,
                    ]
                ),
            ],
        ];
    }

    private function provideValidMessages(): array
    {
        return [
            'DepositInputType valid body' => [
                new SmartcardDepositMessage(
                    1,
                    DepositInputType::class,
                    '04552032216C85',
                    [
                        'reliefPackageId' => 1,
                        'createdAt' => "2020-12-24T00:00:00.000Z",
                    ]
                ),
            ],
            'CreateDepositInputType valid body' => [
                new SmartcardDepositMessage(
                    1,
                    CreateDepositInputType::class,
                    '04552032216C85',
                    [
                        'smartcardSerialNumber' => '04552032216C85',
                        'reliefPackageId' => 1,
                        'balanceBefore' => 1,
                        'balanceAfter' => 1,
                        'createdAt' => "2020-12-24T00:00:00.000Z",
                    ]
                ),
            ],
        ];
    }
}
