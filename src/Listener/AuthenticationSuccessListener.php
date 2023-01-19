<?php

declare(strict_types=1);

namespace Listener;

use Closure;
use Entity\User;
use JetBrains\PhpStorm\Pure;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Utils\UserService;
use Utils\VendorService;

class AuthenticationSuccessListener
{
    private array $methods = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
        private readonly VendorService $vendorService,
        private readonly UserService $userService,
    ) {
        $this->methods = [
            'web-app' => $this->getDataForWebApp(...),
            'offline-app' => $this->getDataForFieldApp(...),
            'vendor-app' => $this->getDataForVendorApp(...),
            'default' => $this->getDataForVendorApp(...),
        ];
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $user = $event->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $method = $this->getMethodForDataRetrieve($request->getUri());

        $data = $method($user);
        $event->setData(array_merge($event->getData(), $data));
    }

    /**
     * @param string $uri
     * @return array
     */
    private function getMethodForDataRetrieve(string $uri): Closure
    {
        if (preg_match('/(?<app>[^\/]+)\/v[\d]/', $uri, $matches)) {
            $app = $matches['app'];
            return key_exists($app, $this->methods) ? $this->methods[$app] : $this->methods['default'];
        }
        return $this->methods['default'];
    }

    /**
     * @param User $user
     * @return array
     */
    private function getDataForVendorApp(User $user): array
    {
        try {
            $vendor = $this->vendorService->getVendorByUser($user);
            $user = $this->userRepository->find($user->getId());
            return [
                'id' => $user->getId(),
                'vendorId' => $vendor->getId(),
                'username' => $user->getUsername(),
                'countryISO3' => $vendor->getLocation()->getCountryIso3(),
            ];
        } catch (NotFoundHttpException) {
            throw new AccessDeniedHttpException(
                'User does not have assigned vendor. You cannot log-in into vendor app.'
            );
        }
    }

    /**
     * @param User $user
     * @return array
     */
    private function getDataForFieldApp(User $user): array
    {
        /** @var User $userEntity */
        $userEntity = $this->userRepository->find($user->getId());
        return [
            'id' => $userEntity->getId(),
            'username' => $userEntity->getUsername(),
            'email' => $userEntity->getEmail(),
            'changePassword' => $userEntity->getChangePassword(),
            'availableCountries' => $this->userService->getAvailableCountries($userEntity),
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    private function getDataForWebApp(User $user): array
    {
        return ['userId' => $user->getId()];
    }

    /**
     * @param $user
     * @return array
     */
    private function getDataForDefaultApp($user): array
    {
        return [];
    }
}
