<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\Query\Expr\Join;

use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GalleryController extends Controller
{
    public function indexAction(Request $request)
    {
        $q = $request->query->get('q');
        $page = $request->query->get('page', 1);
        if(!in_array($request->query->get('sortBy'), ['created', 'owner', 'title'])){
                $request->query->set('sortBy', 'created');
            }else{
                $sortBy = $request->query->get('sortBy');
        }
        if(!in_array($request->query->get('order'), ['asc', 'desc'])){
            $request->query->set( 'order', 'DESC');
        }else{
            $order = $request->query->get('order');
        }
        $resultsPerPage = 8;
        $startingRow = $resultsPerPage * ($page -1);
        $em = $this->getDoctrine()->getManager();
        if($q){
            //$totalRows = $em->getRepository('AppBundle:Image')->countResultRows($q);
            /*$query = $em->getRepository('AppBundle:Image')
                         ->SearchForQuery($q, $resultsPerPage,
                                              $startingRow,
                                              $sortBy,
                                              $order
                                            );*/
            $query = $em->getRepository('AppBundle:Image')->createQueryBuilder('image');
            $query->select('image')
            ->leftJoin('image.owner', 'users', Join::WITH)
            ->where($query->expr()->orX(
                        $query->expr()->like('image.title', ':key'),
                        $query->expr()->like('image.description', ':key'),
                        $query->expr()->like('users.username', ':key')
                        ))
            ->setParameter('key', '%'.$q.'%');
            //$result = $query->getQuery();
            $paginator = $this->get('knp_paginator');
            $pagination = $paginator->paginate($query->getQuery(), $page, $resultsPerPage);
            //$pagination->setTotalItemCount($totalRows);
        }else{
            /*$totalRows = $em->getRepository('AppBundle:Image')
            ->createQueryBuilder('id')
            ->select('COUNT(id)')
            ->getQuery()
            ->getSingleScalarResult();*/
            //$dql = "SELECT image FROM AppBundle:Image image";
            //$query = $em->createQuery($dql);
            $query = $em->getRepository('AppBundle:Image');
            $query->createQueryBuilder();
            $paginator = $this->get('knp_paginator');
            $pagination = $paginator->paginate($query, $page, $resultsPerPage);
                    }
    	return $this->render('AppBundle:Twig:gallery.html.twig', array(
            'title' => 'sandbox|gallery', 'content' => $query, 'pagination' => $pagination));
    }
    public function imageAction($id) //single image
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('AppBundle:Image')->findOneBy(array('id' => $id));
        if(!$image){
            throw $this->createNotFoundException('No image with id '.$id);
            }
        return $this->render('AppBundle:Twig:image.html.twig', array('title' => 'sandbox|image', 'image' => $image));

    }
    public function imageEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('AppBundle:Image')->findOneBy(array('id' => $id));

        if (false === $this->get('security.authorization_checker')->isGranted('edit', $image)) {
            throw new AccessDeniedException('Unauthorised access!');
            }

        $defaultData = array('message' => 'Type your message here');
        $form = $this->createFormBuilder($defaultData)
            ->add('title', 'text', array('data' => $image->getTitle(), 'constraints' => new Length(array('min' => 3), new NotBlank)))
            ->add('description', 'textarea', array( 'data' => $image->getDescription(), 'required' => true))
            ->add('Save', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $image->setTitle($data['title'])->setDescription($data['description'])->setUpdated(new \Datetime());
            $em->flush();
            return $this->redirectToRoute('_image', array('id' => $id));
        }

        return $this->render('AppBundle:Twig:image.html.twig', array('title' => 'sandbox|image', 'image' => $image, 'form' => $form->createView()));
    }
    public function imageDeleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('AppBundle:Image')->findOneBy(array('id' => $id));

        if ($this->get('security.authorization_checker')->isGranted('delete', $image) === false) {
            throw new AccessDeniedException('Unauthorised access!');
        }

        if (!$image) {
                throw $this->createNotFoundException('No image with id '.$id);
            }else {
                $em->remove($image);
                $em->flush();
                $fs = new Filesystem();
                $fs->remove( __DIR__.'/../../../web/images/'.$image->getFileName().'.'.$image->getExtension());
                $fs->remove( __DIR__.'/../../../web/media/cache/thumb/'.$image->getFileName().'.'.$image->getExtension());
                $request->getSession()
                    ->getFlashBag()
                    ->add('success', 'Image deleted!');
                return $this->redirectToRoute('_gallery');
            }
    }
}
