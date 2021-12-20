<?php declare(strict_types=1);

namespace UserBundle\Utils\Firewall;

use UserBundle\Enum\FirewallType;

class FirewallDetector
{
    private const URI_POSITION = 2;

    /**
     * @param string $uri
     *
     * @return string
     */
    public static function detect(string $uri): string
    {
        $parsedUri = explode('/', $uri);
        $firewall = $parsedUri[self::URI_POSITION];

        if(!in_array($firewall, FirewallType::values())){
            throw new UndefinedFirewallException("Firewall $firewall is undefined");
        }

        return $firewall;
    }
}
