<?php

namespace UBC\Exam\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UBC\Exam\MainBundle\Entity\Exam;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

class DefaultController extends Controller
{
    public function indexAction() {
        return $this->render('UBCExamMainBundle:Default:index.html.twig');
    }

    public function uploadAction(Request $request) {
        $exam = new Exam();
        $exam->setYear(date('Y'));
        $exam->setLegalDate(new \DateTime(date('Y-m-d')));

        $form = $this->createFormBuilder($exam)
            ->add('faculty', 'text')
            ->add('dept', 'text')
            ->add('subject_code', 'text')
            ->add('comments', 'textarea')
            ->add('year')
            ->add('term', 'choice', array('choices' => array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD')))
            ->add('cross_listed', 'text', array('required' => false))
            ->add('access_level', 'choice', array('choices' => array('1' => 'Public', '2' => 'CWL', '3' => 'Department', '4' => 'Course')))
            ->add('legal_date', 'date', array('widget' => 'single_text'))
            ->add('legal_content_owner', 'text')
            ->add('legal_uploader', 'text')
            ->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true))
            ->add('file', 'file')
            ->add('upload', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            //setup who did it!
            $user = $this->get('security.context')->getToken()->getUser();
            $exam->setUploadedBy($user);

            //save to DB
            $em->persist($exam);
            $em->flush();

            return $this->redirect($this->generateUrl('exam_upload'));
        }

        return $this->render('UBCExamMainBundle:Default:upload.html.twig', array('form' => $form->createView()));
    }

    public function listAction(Request $request) {
        $exams = $this->getDoctrine()
                    ->getRepository('UBCExamMainBundle:Exam')
                    ->findAll();
        return $this->render('UBCExamMainBundle:Default:list.html.twig', array('exams' => $exams));
    }
    
    /**
     * special call to refresh info from https://courses.students.ubc.ca/cs/main?pname=subjarea&tname=subjareas&req=0
     * I stil need to think about the best way to do this.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function refreshAction(Request $request) {
        //get page content
        $ch = curl_init('https://courses.students.ubc.ca/cs/main?pname=subjarea&tname=subjareas&req=0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_scraped_page = curl_exec($ch);
        curl_close($ch);

        //clean up spaces between elements, cause by default, spaces between element tags are considered textnodes! whaaaaaa?
        $curl_scraped_page_cleaned = preg_replace("/>\s+</", "><", $curl_scraped_page);

        //ok, NOW start parsing
        $crawler = new Crawler($curl_scraped_page_cleaned);
        $crawler = $crawler->filter('body table#mainTable tbody tr');
       	//checks if results empty, then don't update!
        if (count($crawler) > 0) {
            foreach ($crawler as $domElement) {
                $children = $domElement->childNodes;
                foreach ($children as $i => $child) {
    //              echo $i. ': '. $child->nodeValue;
                }
    //          echo '<br>';
            }
        }

        return new Response('<html><head><title>UBC</title></head><body>worked!</body></html>');
    }

    /**
     * not sure what this is.  It's set in security.yaml check_path of the firewalls.ubc_secured_area.trusted_sso.checkpath.
     * 
     * @param unknown $something
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loggedinAction($something) {
            $this->get('logger')->info(print_r($something,true));
            return new Response();
    }
}
