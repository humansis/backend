<?php

namespace UserBundle\Controller;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use UserBundle\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class UserController
 * @package UserBundle\Controller
 */
class UserController extends Controller
{

    /**
     * Log a user with its username and salted password. Create a new one if not in the db (remove this part for prod env)
     * @Rest\Post("/login", name="user_login")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Response(
     *      response=200,
     *      description="SUCCESS",
     *      examples={
     *          "application/json": {
     *              "at"="2018-01-12 12:11:05",
     *              "registered"="true",
     *              "user"="username"
     *          }
     *      }
     * )
     *
     * @SWG\Parameter(
     *     name="username",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="username of the user",
     *     @SWG\Schema()
     * )
     * @SWG\Parameter(
     *     name="salted_password",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="salted password of the user",
     *     @SWG\Schema()
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad credentials (username: myUsername)"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $username = $request->request->get('username');
        $saltedPassword = $request->request->get('salted_password');
        
        $clientIp = $request->headers->get('x-real-ip');
        if ($clientIp) {
            $originRequest = json_decode(file_get_contents('http://www.geoplugin.net/json.gp?ip=' . $clientIp))->geoplugin_countryCode;
            $originISO3 = json_decode(file_get_contents('https://restcountries.eu/rest/v2/alpha/' . $originRequest))->alpha3Code;    
        } else {
            $originISO3 = null;
        }
        
        try
        {
            $user = $this->container->get('user.user_service')->login($username, $saltedPassword, $originISO3);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), Response::HTTP_FORBIDDEN);
        }

        if ($user->getVendor() !== null) {
            return new Response('You cannot connect on this site, please use the app.', Response::HTTP_FORBIDDEN);
        }
        
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        
        $userJson = $serializer->serialize($user, 'json', SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true));
        return new Response($userJson);
    }

    /**
     * Get user's salt
     *
     * @Rest\Get("/salt/{username}")
     *
     * @SWG\Tag(name="Users")
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
     *      examples={
     *          "application/json": {
     *              "user_id" = 1,
     *              "salt" = "fgrgfhjjgh21h5rt"
     *          }
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @SWG\Response(
     *     response=423,
     *     description="LOCKED"
     * )
     *
     * @param $username
     * @return Response
     */
    public function getSaltAction($username)
    {
        try
        {
            $salt = $this->get('user.user_service')->getSalt($username);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), $exception->getCode()>=Response::HTTP_BAD_REQUEST ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($salt);
    }

    /**
     * Get user's salt
     *
     * @Rest\Get("/initialize/{username}")
     *
     * @SWG\Tag(name="Users")
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
     *      examples={
     *          "application/json": {
     *              "user_id" = 1,
     *              "salt" = "fgrgfhjjgh21h5rt"
     *          }
     *      }
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @SWG\Response(
     *     response=423,
     *     description="LOCKED"
     * )
     *
     * @param $username
     * @return Response
     */
    public function initializeAction($username)
    {
        try
        {
            $salt = $this->get('user.user_service')->initialize($username);
        }
        catch (\Exception $exception)
        {
            return new Response($exception->getMessage(), $exception->getCode()>=Response::HTTP_BAD_REQUEST ? $exception->getCode() : Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($salt);
    }

    /**
     * Create a new User. You must have called getSalt before use this one
     *
     * @Rest\Put("/users", name="add_user")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Parameter(
     *     name="user",
     *     in="body",
     *     required=true,
     *     @Model(type=User::class, groups={"FullUser"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="User created",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $user = $request->request->all();
        $userData = $user;

        try
        {
            $user = $serializer->deserialize(json_encode($request->request->all()), User::class, 'json');
            $return = $this->get('user.user_service')->create($user, $userData);

            if (!$user instanceof User)
                return new JsonResponse($user);

            $userJson = $serializer->serialize(
                $return,
                'json',
                SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true)
            );
            return new Response($userJson);
        }
        catch (\Exception $exception)
        {
            if ($user instanceof User) {
                $this->get('user.user_service')->deleteByUsername($user->getUsername());
            } else {
                $this->get('user.user_service')->deleteByUsername($user['username']);
            }

            return new Response($exception->getMessage(), 500);
        }
    }

    /**
     * Connection URL checking
     *
     * @Rest\Get("/check")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Users")
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
     * @Security("is_granted('ROLE_USER_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Response(
     *     response=200,
     *     description="User created",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"FullUser"}))
     *     )
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function getAllAction(Request $request)
    {
        $limit = ($request->query->has('limit'))? $request->query->get('limit') : null;
        $offset = ($request->query->has('offset'))? $request->query->get('offset') : null;

        $users = $this->get('user.user_service')->findAll($limit, $offset);
        $json = $this->get('jms_serializer')->serialize($users, 'json', SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true));

        return new Response($json, Response::HTTP_OK);
    }

    /**	
     * Get web users	
     *	
     * @Rest\Get("/web-users", name="get_web_users")	
     * @Security("is_granted('ROLE_USER_MANAGEMENT_READ')")	
     *	
     * @SWG\Tag(name="Users")	
     *	
     * @SWG\Response(	
     *     response=200,	
     *     description="Users fetched",	
     *     @SWG\Schema(	
     *         type="array",	
     *         @SWG\Items(ref=@Model(type=User::class, groups={"FullUser"}))	
     *     )	
     * )	
     *	
     * @SWG\Response(	
     *     response=400,	
     *     description="BAD_REQUEST"	
     * )	
     *	
     * @param Request $request	
     * @return Response	
     */	
    public function getWebUsersAction(Request $request)	
    {	
        $limit = ($request->query->has('limit'))? $request->query->get('limit') : null;	
        $offset = ($request->query->has('offset'))? $request->query->get('offset') : null;	
         $users = $this->get('user.user_service')->findWebUsers($limit, $offset);	
        $json = $this->get('jms_serializer')->serialize($users, 'json', SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true));	
         return new Response($json, Response::HTTP_OK);	
    }

    /**
     * Show projects of an user
     *
     * @Rest\Get("/users/{id}/projects", name="show_projects_user")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Response(
     *     response=200,
     *     description="User created",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param User $user
     * @return Response
     */
    public function showProjectsAction(User $user)
    {
        $projects = $this->get('user.user_service')->findAllProjects($user);

        $json = $this->get('jms_serializer')
            ->serialize($projects, 'json');

        return new Response($json, Response::HTTP_OK);
    }

    /**
     * Edit a user {id} with data in the body
     *
     * @Rest\Post("/users/{id}", name="update_user")
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Parameter(
     *     name="user",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the user which must be updated",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
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
     * Edit user's language with data in the body
     *
     * @Rest\Post("/users/{id}/language", name="language_user")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function updateLanguage(Request $request, User $user)
    {
        $language = $request->request->get('language');
        $userUpdated = $this->get('user.user_service')->updateLanguage($user, $language);
        $json = $this->get('jms_serializer')->serialize($userUpdated, 'json', SerializationContext::create()->setGroups(['FullUser']));
        return new Response($json);
    }



    /**
     * Change the password of user {id}. Must send oldPassword and newPassword
     *
     * @Rest\Post("/users/{id}/password", name="edit_password_user")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Parameter(
     *     name="oldPassword",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="Current password",
     *     @SWG\Schema(type="string")
     * )
     *
     * @SWG\Parameter(
     *     name="newPassword",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="New password",
     *     @SWG\Schema(type="string")
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=User::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
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
            return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        $userJson = $this->get('jms_serializer')->serialize(
            $user,
            'json',
            SerializationContext::create()->setGroups(['FullUser'])->setSerializeNull(true)
        );

        return new Response($userJson);
    }

    /**
     * Delete an user with its links in the api
     * @Rest\Delete("/users/{id}", name="delete_user")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @param User $user
     * @return Response
     */
    public function deleteAction(User $user)
    {
        $isSuccess = $this->get('user.user_service')->delete($user);

        return new JsonResponse($isSuccess);
    }

    /**
     * Get the log file for the user
     * @Rest\Get("/users/{id}/logs", name="log_user")
     * @Security("is_granted('ROLE_USER_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Users")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Success or not",
     *     @SWG\Schema(type="boolean")
     * )
     *
     * @param User $user
     * @return Response
     */
    public function getLogAction(User $user)
    {
        $userConnected = $this->getUser();
        $attach = $this->get('user.user_service')->getLog($user, $userConnected);

        return new JsonResponse($attach);
    }

}
