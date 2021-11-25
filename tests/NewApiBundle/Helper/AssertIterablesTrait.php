<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

trait AssertIterablesTrait
{
    /**
     * Checks whether $expected array is fully contained in $actual array.
     *
     * @param        $expected
     * @param        $actual
     * @param string $message
     */
    public static function assertArrayFragment($expected, $actual, $message = '')
    {
        $constraint = new \CommonBundle\Utils\Test\Contraint\MatchArrayFragment($expected);

        WebTestCase::assertThat($actual, $constraint, $message);
    }

    /**
     * Checks whether $expected json string is fully contained in $actual json string.
     *
     * @param        $expected
     * @param        $actual
     * @param string $message
     */
    public static function assertJsonFragment($expected, $actual, $message = '')
    {
        WebTestCase::assertJson($expected);
        WebTestCase::assertJson($actual);
        self::assertArrayFragment(json_decode($expected, true), json_decode($actual, true), $message);
    }

}
