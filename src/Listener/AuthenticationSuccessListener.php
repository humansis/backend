<?php

namespace Listener;

use Entity\User;
use JetBrains\PhpStorm\Pure;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Utils\VendorService;

class AuthenticationSuccessListener
{

    private array $methods = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
        private readonly VendorService $vendorService,
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

    private function getMethodForDataRetrieve($uri)
    {
        if (preg_match('/(?<app>[^\/]+)\/v[\d]/', $uri, $matches)) {
            $app = $matches['app'];
            return key_exists($app, $this->methods) ? $this->methods[$app] : $this->methods['default'];
        }
        return $this->methods['default'];
    }

    private function getDataForVendorApp($user): array
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

    private function getDataForFieldApp($user): array
    {
        /** @var User $userEntity */
        $userEntity = $this->userRepository->find($user->getId());
        return [
            'id' => $userEntity->getId(),
            'username' => $userEntity->getUsername(),
            'email' => $userEntity->getEmail(),
            'changePassword' => $userEntity->getChangePassword(),
            'availableCountries' => $userEntity->getAvailableCountries(),
        ];
    }

    private function getDataForWebApp($user): array
    {
        return ['userId' => $user->getId()];
    }

    private function getDataForDefaultApp($user): array
    {
        return [];
    }

}
