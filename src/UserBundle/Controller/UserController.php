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
    public function loginAction(Request $request)
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
        try
        {
            $salt = $this->get('user.user_service')->getSalt($username);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), $exception->getCode());
        }

        return new Response(json_encode($salt));
    }

    /**
     * Create a new User. You must have called getSalt before use this one
     *
     * @Rest\Put("/users", name="add_user")
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $user = $serializer->deserialize(json_encode($request->request->all()), User::class, 'json');
        try
        {
            $return = $this->get('user.user_service')->create($user);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage());
        }

        if (!$user instanceof User)
            return new Response(json_encode($user));


        $userJson = $serializer->serialize(
            $return,
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
    public function checkAction()
    {
        $user = $this->getUser();
        if ($user instanceof User)
        {
            $user = $this->get('jms_serializer')->serialize($user, 'json', SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true));
            return new Response($user, Response::HTTP_OK);
        }
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Get all users
     *
     * @Rest\Get("/users", name="get_all_users")
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
        $json = $this->get('jms_serializer')->serialize($users, 'json', SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Get a user
     *
     * @Rest\Get("/users/{id}", name="show_user")
     *
     * @SWG\Response(
     *     response=200,
     *     description="OK",
     * )
     * @SWG\Tag(name="User")
     *
     * @return Response
     */
    public function showAction(User $user)
    {
        $json = $this->get('jms_serializer')
            ->serialize($user, 'json', SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Edit a user {id} with data in the body
     *
     * @Rest\Post("/users/{id}", name="update_user")
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function updateAction(Request $request, User $user)
    {
        $userData = $request->request->all();
        $userNew = $this->get('user.user_service')->update($user, $userData);
        $json = $this->get('jms_serializer')->serialize($userNew, 'json', SerializationContext::create()->setGroups(['FullUser']));
        return new Response($json);
    }

    /**
     * Change the password of user {id}. Must send oldPassword and newPassword
     *
     * @Rest\Post("/users/{id}/password", name="edit_password_user")
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

    /**
     * Delete an user with its links in the api
     * @Rest\Delete("/users/{id}", name="delete_user")
     *
     * @param User $user
     * @return Response
     */
    public function deleteAction(User $user)
    {
        $isSuccess = $this->get('user.user_service')->delete($user);

        return new Response(json_encode($isSuccess));
    }
}
