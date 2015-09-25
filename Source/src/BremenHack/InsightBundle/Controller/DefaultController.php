<?php

namespace BremenHack\InsightBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BremenHackInsightBundle:Default:index.html.twig', array('name' => $name));
    }
}
