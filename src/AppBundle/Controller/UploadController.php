<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Image;
use AppBundle\Form\Type\UploadFormType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UploadController extends Controller
{
    public function indexAction(Request $request)
    {    
        $image = new Image();       
        $form = $this->createForm(new UploadFormType());
        $user = $this->getUser();
        $form->handleRequest($request);
        if($form->isValid()){ 
            if ($this->get('security.authorization_checker')->isGranted('create', $image, $user) === false) {
                throw new AccessDeniedException('Unauthorised access!');
            }          
            $data = $form->getData();
            $imageSizeDetails = getimagesize($data['file']->getPathName());
            $randomFileName = sha1(uniqid(mt_rand(), true));
            $image->setFileName($randomFileName) //New Random File Name
                  ->setSize($data['file']->getSize())
                  ->setResolution(strval($imageSizeDetails[0]).' x '.strval($imageSizeDetails[1])) //Image resolution in format "width x height"
                  ->setExtension($data['file']->getClientOriginalExtension())
                  ->setTitle($data['title'])
                  ->setDescription($data['description'])
                  ->setOwner($user);
            $data['file']->move(__DIR__.'/../../../web/images', $randomFileName.'.'.$data['file']->getClientOriginalExtension());
            $em = $this->getDoctrine()->getManager();
            $em->persist($image);
            $em->flush();
            $request->getSession()
                ->getFlashBag()
                ->add('success', 'Image uploaded!');
            return $this->redirectToRoute('_gallery');
        } else {
          $request->getSession()
                ->getFlashBag()
                ->add('warning', 'Image upload error!');
        }
        return $this->render('AppBundle:Twig:upload.html.twig', array('title' => 'sandbox|project', 'form' => $form->createView()));
    }
}
