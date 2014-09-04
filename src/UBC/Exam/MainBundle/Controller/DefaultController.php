<?php

namespace UBC\Exam\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UBC\Exam\MainBundle\Entity\Exam;
use UBC\Exam\MainBundle\Entity\Faculty;
use UBC\Exam\MainBundle\Entity\SubjectCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Main controller class
 * 
 * @author loongchan
 *
 */
class DefaultController extends Controller
{
    /**
     * just shows empty page. need to determine what to show on default page.
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function indexAction()
    {
        $sc = $this->get('security.context');
        $isLoggedIn = $sc->isGranted('IS_AUTHENTICATED_FULLY');
        
        return $this->render('UBCExamMainBundle:Default:index.html.twig', array('isLoggedIn' => $isLoggedIn));
    }

    /**
     * Handles upload page
     * 
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction(Request $request)
    {
        $exam = new Exam();
        $exam->setYear(date('Y'));
        $exam->setLegalDate(new \DateTime(date('Y-m-d')));
        
        //get faculties
        $em = $this->getDoctrine()->getManager();
        $facultyQuery = $em->createQueryBuilder()
                ->select(array('f.name'))
                ->from('UBCExamMainBundle:Faculty', 'f');
        $results = $facultyQuery->getQuery()->getResult();
        $faculties = array_map(create_function('$o', 'return $o["name"];'), $results);  //props to http://stackoverflow.com/questions/1118994/php-extracting-a-property-from-an-array-of-objects
        $facultyValues = array_values($faculties);
        $faculties = array_combine($facultyValues, $facultyValues);
        
        $subjectCodeQuery = $em->createQueryBuilder()
                ->select(array('s.code'))
                ->from('UBCExamMainBundle:SubjectCode', 's');
        $results = $subjectCodeQuery->getQuery()->getResult();
        $subjectCode =  array_map(create_function('$s', 'return $s["code"];'), $results);
        $subjectCodeValues = array_values($subjectCode);
        $subjectCode = array_combine($subjectCodeValues, $subjectCodeValues);
        
        $form = $this->createFormBuilder($exam);
        
        if (count($faculties) > 1) {
            $form->add('faculty', 'choice', array('choices' =>$faculties));
        } else {
            $form->add('faculty', 'text');
        }
        
        $form->add('dept', 'text');
        
        if (count($subjectCode) > 1) {
            $form->add('subject_code', 'choice', array('choices' => $subjectCode))
                 ->add('subject_code_number', 'text', array('label' => false, 'mapped' => false));  //extra field to
        } else {
            $form->add('subject_code', 'text');
        }
        
        $form->add('comments', 'textarea')
            ->add('year')
            ->add('term', 'choice', array('choices' => array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD')))
            ->add('cross_listed', 'text', array('required' => false))
            ->add('access_level', 'choice', array('choices' => Exam::$ACCESS_LEVELS))
            ->add('legal_date', 'date', array('widget' => 'single_text'))
            ->add('legal_content_owner', 'text')
            ->add('legal_uploader', 'text')
            ->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true))
            ->add('file', 'file')
            ->add('upload', 'submit');
        
        $form = $form->getForm();

        if ($this->getRequest()->getMethod() == "POST") {
            $form->handleRequest($request);
            $exam->setSubjectcode($exam->getSubjectcode().' '.trim($form->get('subject_code_number')->getData()));
            
            if ($form->isValid()) {
                //setup who did it!
                $user = $this->get('security.context')->getToken()->getUser();
                $exam->setUploadedBy($user);

                //save to DB
                $em->persist($exam);
                $em->flush();

                return $this->redirect($this->generateUrl('exam_upload'));
            }
        }

        return $this->render('UBCExamMainBundle:Default:upload.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * page for listing exams the person has uploaded
     * 
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');

        $query = $repo->createQueryBuilder('e')
                ->where('e.uploaded_by = :user')
                ->setParameter('user', $user)
                ->orderBy('e.created', 'DESC')
                ->getQuery();

        $exams = $query->getResult();

        return $this->render('UBCExamMainBundle:Default:list.html.twig', array('exams' => $exams, 'user' => $user));
    }
    
    /**
     * special call to refresh info from https://courses.students.ubc.ca/cs/main?pname=subjarea&tname=subjareas&req=0
     * I stil need to think about the best way to do this.
     * 
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function refreshAction(Request $request)
    {
        //get page content
        $ch = curl_init('https://courses.students.ubc.ca/cs/main?pname=subjarea&tname=subjareas&req=0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlScrapedPage = curl_exec($ch);
        curl_close($ch);

        //clean up spaces between elements, cause by default, spaces between element tags are considered textnodes! whaaaaaa?
        $curlScrapedPageCleaned = preg_replace("/>\s+</", "><", $curlScrapedPage);

        //ok, NOW start parsing
        $crawler = new Crawler($curlScrapedPageCleaned);
        $crawler = $crawler->filter('body table#mainTable tbody tr');
        
       	//checks if results empty, then don't update!
        if (count($crawler) > 0) {
            $faculties = array();
            $subjectCode = array();
            foreach ($crawler as $domElement) {
                $children = $domElement->childNodes;
                $faculties[] = $children->item(2)->nodeValue;
                $subjectCode[] = preg_replace('/[^A-Z]/', '', $children->item(0)->nodeValue);
            }
            $faculties = array_unique($faculties);
            $subjectCode = array_unique($subjectCode);
            sort($faculties);
            sort($subjectCode);
            
            //dump old faculties and subject code
            $em = $this->getDoctrine()->getEntityManager();
            $cmdF = $em->getClassMetadata('UBC\Exam\MainBundle\Entity\Faculty');
            $cmdSC = $em->getClassMetadata('UBC\Exam\MainBundle\Entity\SubjectCode');
            $connection = $em->getConnection();
            $dbPlatform = $connection->getDatabasePlatform();
            $dbPlatform->getTruncateTableSql($cmdF->getTableName());
            $dbPlatform->getTruncateTableSql($cmdSC->getTableName());
            
            //insert new faculties
            foreach ($faculties as $faculty) {
                $newFaculty = new Faculty();
                $newFaculty->setName($faculty);
                $em->persist($newFaculty);
            }
            foreach ($subjectCode as $code) {
                $newSubjectCode = new SubjectCode();
                $newSubjectCode->setCode($code);
                $em->persist($newSubjectCode);
            }
            $em->flush();
        }
        
        return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
    }

    /**
     * not sure what this is.  It's set in security.yaml check_path of the firewalls.ubc_secured_area.trusted_sso.checkpath.
     * 
     * @param unknown $something
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loggedinAction()
    {
            return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
    }

    /**
     * if you type in login url, it will redirect to main exam page.
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
    }
    
    /**
     * deletes exam.  
     * 
     * @param integer $examID
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteexamAction($examID)
    {
        //checks that an exam id is passed in
        if (!$examID) {
            throw $this->createNotFoundException('No exam selected');
        }
        
        //check that user is uploader, ONLY uploader can delete the exam
        $user = $this->get('security.context')->getToken()->getUser();
        $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');
        $query = $repo->createQueryBuilder('e')
                ->where('e.uploaded_by = :user and e.id = :id')
                ->setParameter('user', $user)
                ->setParameter('id', $examID)
                ->getQuery();
        $exam = $query->getOneOrNullResult();
        
        if (is_null($exam)) {
            throw $this->createNotFoundException('No exam exists for that user');
        }
        
        //remove entity
        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($exam);
        $em->flush();
        
        return $this->redirect($this->generateUrl('list'));
    }
    
    /**
     * allows uploader to update exam
     * 
     * @param unknown $examID
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateexamAction($examID)
    {
        //checks that an exam id is passed in
        if (!$examID) {
            throw $this->createNotFoundException('No exam selected');
        }
        
        //check that user is uploader, ONLY uploader can edit the exam
        $user = $this->get('security.context')->getToken()->getUser();
        $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');
        $query = $repo->createQueryBuilder('e')
        ->where('e.uploaded_by = :user and e.id = :id')
        ->setParameter('user', $user)
        ->setParameter('id', $examID)
        ->getQuery();
        $exam = $query->getOneOrNullResult();

        if (is_null($exam)) {
            throw $this->createNotFoundException('No exam exists for that user');
        }

        //ok, create update form!
        $form = $this->createFormBuilder($exam)
        ->add('faculty', 'text')
        ->add('dept', 'text')
        ->add('subject_code', 'text')
        ->add('comments', 'textarea')
        ->add('year')
        ->add('term', 'choice', array('choices' => array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD')))
        ->add('cross_listed', 'text', array('required' => false))
        ->add('access_level', 'choice', array('choices' => Exam::$ACCESS_LEVELS))
        ->add('legal_date', 'date', array('widget' => 'single_text'))
        ->add('legal_content_owner', 'text')
        ->add('legal_uploader', 'text')
        ->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true))
        ->add('file', 'file')
        ->add('upload', 'submit')
        ->getForm();

        if ($this->getRequest()->getMethod() == "POST") {
            $form->handleRequest($request);
    
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                //setup who did it!
                $user = $this->get('security.context')->getToken()->getUser();
                $exam->setUploadedBy($user);
    
                //save to DB
                $em->persist($exam);
                $em->flush();
            }
        }
            return $this->render('UBCExamMainBundle:Default:update.html.twig', array('form' => $form->createView()));
    }
}
