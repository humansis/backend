<?php

namespace Tests\NewApiBundle\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UserBundle\Utils\UserService;

class UserServiceTest extends KernelTestCase
{
    /** @var UserService */
    private $userService;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->bootKernel();

        $this->userService = self::$kernel->getContainer()->get(UserService::class);
    }

    public function hashDataProvider()
    {
        return [
            [' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~', ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~', 'BpiJaPJB1xuJtH9nkKGRukWr42DjqRaVRlTwQSk8+4E9f15DhVMRBNXk85/G8E38PMDfmBy5JM3JjpjSWMk1Fw=='],
            ['ěščřž', 'ýááíé', 'Hp4L53XOsycX6djfbUY4L8eMDhcR0Lmntqrw0l9tmEFxzIq1LpE+vQ0wVXffLiPT/mZVwrPwL1ux26MYBtvCnA=='],
        ];
    }

    /**
     * @dataProvider hashDataProvider
     *
     * @param string $password
     * @param string $salt
     * @param string $expectedHash
     */
    public function testHash(string $password, string $salt, string $expectedHash)
    {
        $this->assertEquals($expectedHash, $this->userService->hashPassword($password, $salt));
    }
}
