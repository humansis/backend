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

        if ($idUser && $method != 'GET' && explode('\\', $controller)[0] != "ReportingBundle") {
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

            $data = [$url, $idUser, $mailUser, $method, (new \DateTime())->format('Y-m-d h:i:s'), $httpStatus, $controller];
            $this->recordLog($idUser, $data);
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

        return ['id' => $user->getId(), 'email' => $user->getEmail()];
    }

    /**
     * Save log record in file
     * @param int $idUser
     * @param  array $data
     * @return void
     */
    public function recordLog(int $idUser, array $data)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (! is_dir($dir_var)) mkdir($dir_var);
        $file_record = $dir_var . '/record_log-' . $idUser . '.csv';

        $fp = fopen($file_record, 'a');
        if (!file_get_contents($file_record))
            fputcsv($fp, array('URL', 'ID user', 'Email user', 'Method', 'Date', 'HTTP Status', 'Controller called') ,";");

        fputcsv($fp, $data, ";");

        fclose($fp);
    }

}