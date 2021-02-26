<?php
namespace UserBundle\Security\Authentication\Provider;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UserBundle\Security\Authentication\Token\WsseUserToken;

class WsseProvider implements AuthenticationProviderInterface
{
    const TIME_BOUND = 5400; // 5400 sec = 1.5 hour

    private $userProvider;
    private $cacheDir;
    private $logger;

    public function __construct(UserProviderInterface $userProvider, $cacheDir, LoggerInterface $logger)
    {
        $this->userProvider = $userProvider;
        $this->cacheDir     = $cacheDir;
        $this->logger       = $logger;
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByUsername($token->getUsername());
        if ($user && $this->validateDigest($token->digest, $token->nonce, $token->created, $user->getPassword())) {
            $authenticatedToken = new WsseUserToken($user->getRoles());
            $authenticatedToken->setUser($user);
            return $authenticatedToken;
        }

        $this->logger->error('wsse authentication failed', [
            'username' => $token->getUsername(),
            'password' => $user ? $user->getPassword() : null,
            'digest' => $token->digest,
            'nonce' => $token->nonce,
            'created' => $token->created,
            'current' => time(),
        ]);
        throw new AuthenticationException('The WSSE authentication failed.');
    }

    /**
     * This function is specific to Wsse authentication and is only used to help this example
     *
     * For more information specific to the logic here, see
     * https://github.com/symfony/symfony-docs/pull/3134#issuecomment-27699129
     */
    protected function validateDigest($digest, $nonce, $created, $secret)
    {
        // Check created time is not so far in the future 5 min (date issue with the api)
        if (strtotime($created) - time() > self::TIME_BOUND) {
            $this->logger->error('wsse validation failed (created time)', [
                'token_time' => $created,
                'server_time' => time(),
                'conditition_time' => strtotime($created) - time(),
            ]);
            return false;
        }

        // Expire timestamp after 5 minutes
        if (time() - strtotime($created) > self::TIME_BOUND) {
            $this->logger->error('wsse validation failed (expire time)', [
                'token_time' => $created,
                'server_time' => time(),
                'conditition_time' => time() - strtotime($created),
            ]);
            return false;
        }

        // Validate that the nonce is *not* used in the last 5 minutes
        // if it has, this could be a replay attack
        if (file_exists($this->cacheDir.'/'.$nonce) && file_get_contents($this->cacheDir.'/'.$nonce) + self::TIME_BOUND > time()) {
            $this->logger->error('wsse validation failed (Previously used nonce detected)', [
                'token_time' => $created,
                'server_time' => time(),
                'conditition_time' => file_get_contents($this->cacheDir.'/'.$nonce) + 300,
            ]);
            throw new NonceExpiredException('Previously used nonce detected');
        }
        // If cache directory does not exist we create it
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        file_put_contents($this->cacheDir.'/'.$nonce, time());

        // Validate Secret
        $expected = base64_encode(sha1(base64_decode($nonce).$created.$secret, true));
        $this->logger->error('wsse validation ok', [
            'token_time' => $created,
            'server_time' => time(),
            'nonce' => $nonce,
            'expected' => $expected,
        ]);
        return Hash_equals($expected, $digest);
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof WsseUserToken;
    }
}
