<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use UserBundle\Entity\User;

class UserController extends Controller
{
	/**
    * Get users
    * @Rest\Get("/users")
    *
    * @SWG\Response(
    *     response=200,
    *     description="OK",
    * )
    * @SWG\Tag(name="Users")
    *
    * @return Response
    */
    public function getUsersAction()
    {
		// TODO check user rights

        $users = $this->get('user.user_service')->findAll();
        $json = $this->get('serializer')->serialize($users, 'json');

        return new Response($json, Response::HTTP_OK);
    }

	/**
    * Get lapin
    * @Rest\Get("/lapin")
    *
    * @SWG\Response(
    *     response=200,
    *     description="OK",
    * )
    * @SWG\Tag(name="Users")
    *
    * @return Response
    */
    public function getLapinAction()
    {
		$json = json_encode(array('email' => "lapin"));

        return new Response($json, Response::HTTP_OK);
    }
}
