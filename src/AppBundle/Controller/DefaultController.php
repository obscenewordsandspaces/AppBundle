<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Type\ContactFormType;


class DefaultController extends Controller
{

    const ITEMS_PER_PAGE = 4;

    public function indexAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $recent        = $entityManager->getRepository('AppBundle:Image')
                                       ->findBy(array(), array('created' => 'DESC'), self::ITEMS_PER_PAGE);

        return $this->render('AppBundle:Twig:index.html.twig', array('title' => 'sandbox|project', 'recent' => $recent));

    }


    public function aboutAction(Request $request)
    {
        $form     = $this->createForm(ContactFormType::class);

        $form->handleRequest($request);

        if ($form->isValid() === true) {
            $data    = $form->getData();
            $message = \Swift_Message::newInstance()
            ->setContentType("text/html")
            ->setSubject($data['subject'])
            ->setFrom($data['from'])
            ->setTo('robot@codesandbox.info')
            ->setBody(
                $this->renderView(
                    'AppBundle:Email:contact.txt.twig',
                    array(
                     'message' => $data['message'],
                     'user'    => $this->getUser(),
                    )
                )
            );
            $this->get('mailer')->send($message);

            $flash = $this->get('braincrafted_bootstrap.flash');
            $flash->success('Message sent, thank you!');

            return $this->redirectToRoute('_about');
        }

        return $this->render('AppBundle:Twig:about.html.twig', array('title' => 'sandbox|about', 'form' => $form->createView()));

    }


    public function apiDemoAction()
    {
        return $this->render('AppBundle:Twig:apiDemo.html.twig', array('title' => "REST'ful API Demo"));
    }

    public function loginAction()
    {
        $error = $this->get('security.authentication_utils')
            ->getLastAuthenticationError();
        return $this->redirectToRoute('_about');

    }


    public function logoutAction()
    {
    }


}
