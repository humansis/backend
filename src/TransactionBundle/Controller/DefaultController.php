<?php

namespace TransactionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('TransactionBundle:Default:index.html.twig');
    }
}
