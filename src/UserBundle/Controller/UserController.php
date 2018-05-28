<?php

namespace UserBundle\Controller;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use UserBundle\Entity\User;

class UserController extends Controller
{

    /**
     * Log a user with its username and salted password. Create a new one if not in the db (remove this part for prod env)
     * @Rest\Post("/login", name="user_login")
     *
     * @param Request $request
     * @return Response
     */
    public function LoginAction(Request $request)
    {

        $username = $request->request->get('username');
        $saltedPassword = $request->request->get('salted_password');
        try
        {
            $data = $this->container->get('user.user_service')->login($username, $saltedPassword);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), $exception->getCode());
        }

        return new Response(json_encode($data));
    }

    /**
     * Get user's salt
     *
     * @Rest\Get("/salt/{username}")
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
        $salt = $this->get('user.user_service')->getSalt($username);

        return new Response(json_encode($salt));
    }

    /**
     * Create a new User. You must have called getSalt before use this one
     *
     * @Rest\Put("/user", name="add_user")
     *
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $user = $serializer->deserialize(json_encode($request->request->all()), User::class, 'json');

        try
        {
            $userSaved = $this->get('user.user_service')->create($user);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage());
        }

        $userJson = $serializer->serialize(
            $userSaved,
            'json',
            SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true)
        );

        return new Response($userJson);
    }

    /**
     * Connection URL checking
     *
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
     * Get all users
     *
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
    public function getAllAction()
    {

        // TODO check user rights

        $users = $this->get('user.user_service')->findAll();
        $json = $this->get('serializer')->serialize($users, 'json');

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Edit a user {id} with data in the body
     *
     * @Rest\Post("/user/{id}", name="edit_user")
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function postAction(Request $request, User $user)
    {
        $userData = $request->request->all();
        $userNew = $this->get('user.user_service')->update($user, $userData);
        $json = $this->get('serializer')->serialize($userNew, 'json');
        return new Response($json);
    }

    /**
     * Change the password of user {id}. Must send oldPassword and newPassword
     *
     * @Rest\Post("/user/{id}/password", name="edit_password_user")
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function postPasswordAction(Request $request, User $user)
    {
        $oldPassword = $request->request->get('oldPassword');
        $newPassword = $request->request->get('newPassword');
        try
        {
            $user = $this->get('user.user_service')->updatePassword($user, $oldPassword, $newPassword);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage());
        }

        $userJson = $this->get('jms_serializer')->serialize(
            $user,
            'json',
            SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true)
        );

        return new Response(json_encode($userJson));
    }
}
