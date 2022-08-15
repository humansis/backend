<?php


namespace NewApiBundle\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use NewApiBundle\Entity\User;
use NewApiBundle\Entity\Vendor;

class RequestListener
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * @param GetResponseEvent $event
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::SUB_REQUEST === $event->getRequestType()) {
            return;
        }

        $disableListener = $event->getRequest()->attributes->get('disable-common-request-listener');

        if ($disableListener) {
            return;
        }

        if ($event->getRequest()->headers->has('country')) {
            $countryISO3 = $event->getRequest()->headers->get('country');
            
            if ($this->getUser()) {
                $user = $this->em->getRepository(User::class)->find($this->getUser());

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

                if ($user->getRoles()[0] === "ROLE_VENDOR") {
                    $country = $this->em->getRepository(Vendor::class)->getVendorCountry($user);

                    if ($countryISO3 === $country) {
                        $hasCountry = true;
                    }
                }

                // hack to prevent E403 on old mobile app with countries sent by mistake before user can change country
                $fallbackCountry = $countryISO3;
                if (!$user->getCountries()->isEmpty()) {
                    $fallbackCountry = $user->getCountries()->first()->getIso3();
                } elseif (!$user->getProjects()->isEmpty()) {
                    $fallbackCountry = $user->getProjects()->first()->getProject()->getIso3();
                } elseif ($user->getRoles()[0] === "ROLE_VENDOR") {
                    $fallbackCountry = $this->em->getRepository(Vendor::class)->getVendorCountry($user);
                }

                if ($user->getRoles()[0] == "ROLE_ADMIN" || $hasCountry) {
                    $event->getRequest()->request->add(["__country" => $countryISO3]);
                } else {
                    if ($fallbackCountry === $countryISO3) {
                        $this->logger->error("You are not allowed to access data for this country", [$countryISO3]);
                        $response = new Response("You are not allowed to access data for this country", Response::HTTP_FORBIDDEN);
                        $event->setResponse($response);
                    } else {
                        $event->getRequest()->request->add(["__country" => $fallbackCountry]);
                    }
                }
            } else {
                $event->getRequest()->request->add(["__country" => $countryISO3]);
            }
        }
        // return error response if api request (i.e. not profiler or doc) or login routes (for api tester)
        elseif (preg_match('/api/', $event->getRequest()->getPathInfo()) &&
                !preg_match('/api\/wsse\/(login|salt)/', $event->getRequest()->getPathInfo())) {
            $this->container->get('logger')->error("'country' header missing from request (iso3 code).", [$event->getRequest()->getPathInfo()]);
            $response = new Response("'country' header missing from request (iso3 code).", Response::HTTP_BAD_REQUEST);
            $event->setResponse($response);
        }
    }

    /**
     * Get the user
     */
    protected function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user->getId();
    }
}
