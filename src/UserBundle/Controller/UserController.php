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
     * Get user's salt
     * @Rest\Get("/salt")
     *
     * @SWG\Parameter(
     *     name="username",
     *     in="query",
     *     type="string",
     *     required=true,
     *     description="username of the user"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @SWG\Schema(
     *         type="string"
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     * @SWG\Response(
     *     response=423,
     *     description="LOCKED"
     * )
     *
     * @SWG\Tag(name="Users")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getSaltAction(Request $request)
    {
        $username = $request->get('username');
        $user = $this->get('user.user_service')->getUserByUsername($username);
        if ($user)
        {
            if ($user->isEnabled())
            {
                $json = $this->get('serializer')->serialize($user->getSalt(), 'json');

                return new Response($json, Response::HTTP_OK);
            }
            return new Response(null, Response::HTTP_LOCKED);
        }

        return new Response(null, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Connection URL checking
     * @Rest\Get("/check")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="UNAUTHORIZED"
     * )
     * @SWG\Tag(name="Users")
     */
    public function getCheckAction()
    {
        $user = $this->getUser();
        if ($user instanceof User)
        {
            $user = $this->get('serializer')->serialize($user, 'json');
            return new Response($user, Response::HTTP_OK);
        }
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }

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
     * @Rest\Post("/user/{id}", name="edit_user")
     *
     * @param Request $request
     * @param User $user
     */
    public function postAction(Request $request, User $user)
    {

    }

    /**
     * @Rest\Put("/user", name="add_user")
     *
     * @param Request $request
     */
    public function addAction(Request $request)
    {

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
        $json = json_encode(array(array('email' => "lapin")));

        return new Response($json, Response::HTTP_OK);
    }
}
