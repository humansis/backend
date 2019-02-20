<?php


namespace CommonBundle\Listener;


use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;

class RequestListener
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->headers->has('country'))
        {
            $countryISO3 = $event->getRequest()->headers->get('country');
            
            if ($this->getUser()) {
                $user = $this->em->getRepository(User::class)->find($this->getUser());

                $countries = $user->getCountries()->getValues();
                $projects = $user->getUserProjects()->getValues();
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

                if ($user->getRoles()[0] == "ROLE_ADMIN" || $hasCountry) {
                    $event->getRequest()->request->add(["__country" => $countryISO3]);
                }
                else {
                    $response = new Response("You are not allowed to acces data for this country", Response::HTTP_FORBIDDEN);
                    $event->setResponse($response);
                }
            }
            else {
                $event->getRequest()->request->add(["__country" => $countryISO3]);
            }
        }
        // return error response if api request (i.e. not profiler or doc) or login routes (for api tester)
        elseif (preg_match('/api/', $event->getRequest()->getPathInfo()) &&
                !preg_match('/api\/(login || salt)/', $event->getRequest()->getPathInfo()))
        {
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
