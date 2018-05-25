<?php

namespace UserBundle\Controller\Dev;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use UserBundle\Entity\User;

/**
 * Class TesterController
 */
class TesterController extends Controller
{
    /**
     * @Get("/user_salt/{username}", name="tester_get_")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \ReflectionException
     */
    public function getSaltAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $username = $request->get('username');

        $repo = $this->getDoctrine()->getRepository(User::class);
        $userSearch = $repo->findOneByUsername($username);

        $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');

        //If the user has already been created, only refresh the salt
        if ($userSearch instanceOf User)
        {
            if (empty($userSearch->getSalt()))
            {
                $userSearch->setSalt($salt);
            }
            $salt = $userSearch->getSalt();
        }
        else
        { //otherwise, create it
            $r = new \ReflectionClass(User::class);

            $userSearch = $r->newInstance();
            $userSearch->setUsername($username);
            $userSearch->setSalt($salt);
            $userSearch->setEmail($username . "@" . $username . ".fr");
            $userSearch->setPassword("");
            $userSearch->setEnabled(1);
            $entityManager->persist($userSearch);
        }

        $entityManager->flush();
        $data = [
            'user_id' => $userSearch->getId(),
            'salt' => $salt
        ];

        return new JsonResponse($data);
    }

    /**
     * @Post("/login", name="tester_login")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function LoginAction(Request $request)
    {

        $username = $request->request->get('username');
        $saltedPassword = $request->request->get('salted_password');

        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository('UserBundle:User');

        try
        {
            $user = $repository->findOneBy([
                'username' => $username,
                'password' => $saltedPassword,
            ]);

            if ($user instanceOf User)
            {
                $data = [
                    'at' => time(),
                    'connected' => true,
                    'user' => $user->getUsername()
                ];

            }
            else
            {
                $user = $repository->findOneBy([
                    'username' => $username
                ]);
                if ($user instanceOf User)
                {
                    $user->setPassword($saltedPassword);
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $data = [
                        'at' => time(),
                        'registered' => true,
                        'user' => $user->getUsername()
                    ];
                }
                else
                {
                    throw new \Exception('Bad credentials (username: ' . $username . ', password: ' . $saltedPassword . ')');
                }

            }
        }
        catch (\Exception $e)
        {
            return new JsonResponse($e->getMessage());
        }

        return new JsonResponse($data);
    }
}