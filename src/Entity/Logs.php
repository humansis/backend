<?php

namespace Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Logs
 *
 * @ORM\Table(name="logs")
 * @ORM\Entity(repositoryClass="Repository\LogsRepository")
 */
class Logs
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @SymfonyGroups({"FullLogs"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     *
     * @SymfonyGroups({"FullLogs"})
     */
    private $url;

    /**
     * @var int
     *
     * @ORM\Column(name="idUser", type="integer")
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="mailUser", type="string", length=255)
     *
     * @SymfonyGroups({"FullLogs"})
     */
    private $mailUser;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=255)
     *
     * @SymfonyGroups({"FullLogs"})
     */
    private $method;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     * @SymfonyGroups({"FullLogs"})
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="httpStatus", type="integer")
     *
     * @SymfonyGroups({"FullLogs"})
     */
    private $httpStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="controller", type="string", length=255)
     * @SymfonyGroups({"FullLogs"})
     */
    private $controller;

    /**
     * @var array
     *
     * @ORM\Column(name="request", type="text")
     *
     * @SymfonyGroups({"FullLogs"})
     */
    protected $request;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Logs
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set idUser.
     *
     * @param int $idUser
     *
     * @return Logs
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser.
     *
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set mailUser.
     *
     * @param string $mailUser
     *
     * @return Logs
     */
    public function setMailUser($mailUser)
    {
        $this->mailUser = $mailUser;

        return $this;
    }

    /**
     * Get mailUser.
     *
     * @return string
     */
    public function getMailUser()
    {
        return $this->mailUser;
    }

    /**
     * Set method.
     *
     * @param string $method
     *
     * @return Logs
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set date.
     *
     * @param DateTime $date
     *
     * @return Logs
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set httpStatus.
     *
     * @param int $httpStatus
     *
     * @return Logs
     */
    public function setHttpStatus($httpStatus)
    {
        $this->httpStatus = $httpStatus;

        return $this;
    }

    /**
     * Get httpStatus.
     *
     * @return int
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

    /**
     * Set controller.
     *
     * @param string $controller
     *
     * @return Logs
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Get controller.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Set request.
     *
     * @param array $request
     *
     * @return Logs
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request.
     *
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }
}
