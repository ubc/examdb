<?php

namespace UBC\Exam\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UBC\Exam\MainBundle\Entity\Exam;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction() {
        return $this->render('UBCExamMainBundle:Default:index.html.twig');
    }
    
    public function uploadAction(Request $request) {
    	$exam = new Exam();
    	$exam->setYear(date('Y'));
    	
    	$form = $this->createFormBuilder($exam)
    	->add('dept', 'text')
    	->add('number', 'text')
		->add('comments', 'text')
		->add('year')
		->add('term')
		->add('access_level')
		->add('file', 'file')
		->add('upload', 'submit')
    	->getForm();
    	
    	$form->handleRequest($request);
    	
    	if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			
// 			$exam->upload();
			
			$em->persist($exam);
			$em->flush();
			
			return $this->redirect($this->generateUrl('exam_upload'));
    	}
    	
    	return $this->render('UBCExamMainBundle:Default:upload.html.twig', array('form' => $form->createView()));
    }
}
