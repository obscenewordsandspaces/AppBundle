<?php

namespace AppBundle\Controller;

use AppBundle\Helpers\Paginator;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Image;

class SearchController extends Controller
{
	public function indexAction(Request $request )
	{
		
		$q = $request->query->get('q');
		$page = $request->query->get('page');
		if(!$page){ 
			$page = 1; 
		}
		$result = null;
		$pageList = null;
		$lastPage = null;
		$resultsPerPage = 8;
		if($q){
			$startingItem = $resultsPerPage * ($page - 1) ;
        	$em = $this->getDoctrine()->getManager();
            $totalRows = count($em->getRepository('AppBundle:Image')->SearchForQuery($q));
			$result = $em->getRepository('AppBundle:Image')->SearchForQuery($q, $resultsPerPage, $startingItem);
			
            $lastPage = ceil($totalRows / $resultsPerPage);

            $paginator = new Paginator($page, $totalRows, $resultsPerPage);
            $pageList = $paginator->getPagesList();
		}
        return $this->render('AppBundle:Twig:search.html.twig', array(
        	'title' => 'sandbox|project',
        	'result' => $result, 
        	'page' => $page, 
        	'pageList' => $pageList, 
        	'lastPage' => $lastPage));
	}
}