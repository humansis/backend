<?php

namespace CommonBundle\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Vendor;

class RequestListener
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthorizationCheckerInterface */
    private $authChecker;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authChecker
    ) {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authChecker = $authChecker;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->headers->has('country')) {
            $countryISO3 = $event->getRequest()->headers->get('country');

            if ($user = $this->getUser()) {
                $countries = $user->getCountries()->getValues();
                $projects = $user->getProjects()->getValues();
                $hasCountry = false;

                foreach ($countries as $country) {
                    if ($country->getIso3() == $countryISO3) {
                        $hasCountry = true;
                        break;
                    }
                }

                foreach ($projects as $project) {
                    if ($project->getProject()->getIso3() == $countryISO3) {
                        $hasCountry = true;
                        break;
                    }
                }

                if ($this->authChecker->isGranted('ROLE_VENDOR')) {
                    $country = $this->em->getRepository(Vendor::class)->getVendorCountry($user);

                    if ($countryISO3 === $country) {
                        $hasCountry = true;
                    }
                }

                if ($this->authChecker->isGranted('ROLE_ADMIN') || $hasCountry) {
                    $event->getRequest()->request->add(['__country' => $countryISO3]);
                } else {
                    $response = new Response('You are not allowed to acces data for this country', Response::HTTP_FORBIDDEN);
                    $event->setResponse($response);
                }
            } else {
                $event->getRequest()->request->add(['__country' => $countryISO3]);
            }
        } // return error response if api request (i.e. not profiler or doc) or login routes (for api tester)
        elseif (preg_match('/api/', $event->getRequest()->getPathInfo()) &&
            !preg_match('/api\/wsse\/(login|salt)/', $event->getRequest()->getPathInfo())) {
            $response = new Response("'country' header missing from request (iso3 code).", Response::HTTP_BAD_REQUEST);
            $event->setResponse($response);
        }
    }

    /**
     * Get the user.
     */
    protected function getUser(): ?User
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        /* @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
