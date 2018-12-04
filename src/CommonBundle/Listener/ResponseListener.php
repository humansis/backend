<?php
/**
 * Created by PhpStorm.
 * User: developer3
 * Date: 3/12/18
 * Time: 15:03
 */

namespace CommonBundle\Listener;


use CommonBundle\Entity\Logs;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Security;

class ResponseListener
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

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response  = $event->getResponse();
        $request   = $event->getRequest();
        $user = $this->getUser();

        //Uid
        $idUser = $user['id'];
        //Umail
        $mailUser = $user['email'];
        //Controller
        $controller = $request->attributes->get('_controller');
        //url
        $url = $request->getPathInfo();
        //Date
        $date = new DateTime('now');
        //Method
        $method = $request->getMethod();
        //HTTPStatus
        $httpStatus = $response->getStatusCode();

        $bundle = explode("\\", $controller);
        if ($bundle[0] == 'BeneficiaryBundle' || $bundle[0] == 'CommonBundle' || $bundle[0] == 'DistributionBundle' || $bundle[0] == 'ProjectBundle' || $bundle[0] == 'ReportingBundle' || $bundle[0] == 'TransactionBundle' || $bundle[0] == 'UserBundle') {
            $log = new Logs();

            $log->setUrl($url)
                ->setIdUser($idUser)
                ->setMailUser($mailUser)
                ->setMethod($method)
                ->setDate($date)
                ->setHttpStatus($httpStatus)
                ->setController($controller);

            if (!$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration()
                );
            }

            $this->em->persist($log);
            $this->em->flush();
        }
    }

    /**
     *
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

        return ['id' => $user->getId(), 'email' => $user->getEmail()];
    }

}