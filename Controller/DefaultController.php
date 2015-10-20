<?php

namespace CMS\FormWizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('FormWizardBundle:Default:index.html.twig', array('name' => $name));
    }
}
