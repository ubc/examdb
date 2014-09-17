<?php

namespace UBC\Exam\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UBC\Exam\MainBundle\Entity\Exam;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Main controller class
 *  
 * @author Loong Chan <loong.chan@ubc.ca>
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
        
        $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');
        $query = $repo->createQueryBuilder('e')
        ->orderBy('e.created', 'DESC')
        ->getQuery();
        
        $exams = $query->getResult();
// $env = $this->container->get('kernel')->getEnvironment();
// print_r($env);
// echo '<br><hr><br>';
// var_dump($isLoggedIn);
// exit();
        return $this->render('UBCExamMainBundle:Default:index.html.twig', array('caption' => 'List of Exams', 'isLoggedIn' => $isLoggedIn, 'exams' => $exams));
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
        $subjectFacultyQuery = $em->createQueryBuilder()
                    ->select(array('s.code', 's.faculty'))
                    ->from('UBCExamMainBundle:SubjectFaculty', 's');
        $results = $subjectFacultyQuery->getQuery()->getResult();
        $faculties = array_map(create_function('$o', 'return $o["faculty"];'), $results);  //props to http://stackoverflow.com/questions/1118994/php-extracting-a-property-from-an-array-of-objects
        $subjectCode = array_map(create_function('$o', 'return $o["code"];'), $results);  //props to http://stackoverflow.com/questions/1118994/php-extracting-a-property-from-an-array-of-objects
        $facultiesValue = array_unique(array_values($faculties));
        $subjectCodeValue = array_unique(array_values($subjectCode));
        asort($facultiesValue);
        asort($subjectCodeValue);
        $faculties = array_combine($facultiesValue, $facultiesValue);
        $subjectCode = array_combine($subjectCodeValue, $subjectCodeValue);
        
        //get user
        $user = $this->get('security.context')->getToken()->getUser();
        $first = $user->getFirstname() != ' ' ? null : $user->getFirstname().' ';
        $last = $user->getLastname() != ' ' ? null : $user->getLastname();
        
        $form = $this->createFormBuilder($exam);
        
        if (count($faculties) > 1) {
            $form->add('faculty', 'choice', array('empty_value' => '- Choose faculty -','choices' => $faculties));
        } else {
            $form->add('faculty', 'text', array('max_length' => 50));
        }
        
        $form->add('dept', 'text', array('max_length' => 50));
        
        if (count($subjectCode) > 1) {
            $form->add('subject_code', 'choice', array('empty_value' => '- Choose subject -', 'choices' => $subjectCode))
                 ->add('subject_code_number', 'text', array('label' => false, 'mapped' => false, 'max_length' => 5));  //extra field to
        } else {
            $form->add('subject_code', 'text', array('max_length' => 10));
        }
        
        $form->add('comments', 'textarea', array('required' => false))
            ->add('year')
            ->add('term', 'choice', array('empty_value' => '- Choose term -', 'choices' => array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD')))
            ->add('cross_listed', 'text', array('required' => false, 'max_length' => 10))
            ->add('access_level', 'choice', array('empty_value' => '- Choose access level -', 'choices' => Exam::$ACCESS_LEVELS))
            ->add('legal_date', 'date', array('widget' => 'single_text', 'read_only' => true))
            ->add('legal_content_owner', 'text', array('max_length' => 100))
            ->add('legal_uploader', 'text', array('data' => $first.$last, 'max_length' => 100))
            ->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true))
            ->add('file', 'file')
            ->add('upload', 'submit');
        
        $form = $form->getForm();

        if ($this->getRequest()->getMethod() == "POST") {
            $form->handleRequest($request);
            $form_subject_code_number = $exam->getSubjectcode();
            
            //need try/catch so that it doesn't puke if subject_code_number doesn't exist
            if ($form->has('subject_code_number')) {
                $form_subject_code_number = $exam->getSubjectcode().' '.trim($form->get('subject_code_number')->getData());
            }

            $exam->setSubjectcode($form_subject_code_number);
            
            if ($form->isValid()) {
                //setup who did it!
                $user = $this->get('security.context')->getToken()->getUser();
                $exam->setUploadedBy($user);

                //save to DB
                $em->persist($exam);
                $em->flush();

                return $this->redirect($this->generateUrl('list'));
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
        $exams = array();
        
        $query = $repo->createQueryBuilder('e')
                ->where('e.uploaded_by = :user')
                ->setParameter('user', $user)
                ->orderBy('e.created', 'DESC')
                ->getQuery();
        $exams = $query->getResult();
        
        return $this->render('UBCExamMainBundle:Default:list.html.twig', array('exams' => $exams, 'username' => $user->getUsername()));
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
            $subjectsFaculties = array();
            foreach ($crawler as $domElement) {
                $children = $domElement->childNodes;
                $subjectsFaculties[] = array('code' => preg_replace('/[^A-Z]/', '', $children->item(0)->nodeValue),
                                        'title' => $children->item(1)->nodeValue,
                                        'faculty' => $children->item(2)->nodeValue);
            }
            
            //dump old subjectfaculty table
            $em = $this->getDoctrine()->getEntityManager();
            $cmd = $em->getClassMetadata('UBC\Exam\MainBundle\Entity\SubjectFaculty');
            $dbPlatform = $em->getConnection()->getDatabasePlatform();
            $dbPlatform->getTruncateTableSql($cmd->getTableName());
            
            //insert new subjectfaculties
            foreach ($subjectsFaculties as $subjectFaculty) {
                $newSubjectFaculty = new SubjectFaculty();
                $newSubjectFaculty->setCode($subjectFaculty['code']);
                $newSubjectFaculty->setTitle($subjectFaculty['title']);
                $newSubjectFaculty->setFaculty($subjectFaculty['faculty']);
                $em->persist($newSubjectFaculty);
            }
            $em->flush();
        }
        
        return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
    }

    /**
     * not sure what this is.  It's set in security.yaml check_path of the firewalls.ubc_secured_area.trusted_sso.checkpath.
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loggedinAction()
    {
        //we delete layout cache so that the menu can reset to whatever it's supposed to me (login button or logout buton)
        $fileCache = $this->container->get('twig')->getCacheFilename('UBCExamMainBundle:layout.html.twig');
        if (is_file($fileCache)) {
            @unlink($fileCache);
        }

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
    public function updateexamAction($examID, Request $request)
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
        ->add('faculty', 'text', array('max_length' => 50))
        ->add('dept', 'text', array('max_length' => 50))
        ->add('subject_code', 'text', array('max_length' => 10))
        ->add('comments', 'textarea', array('required' => false))
        ->add('year')
        ->add('term', 'choice', array('choices' => array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD')))
        ->add('cross_listed', 'text', array('required' => false, 'max_length' => 10))
        ->add('access_level', 'choice', array('choices' => Exam::$ACCESS_LEVELS))
        ->add('legal_date', 'date', array('widget' => 'single_text', 'read_only' => true))
        ->add('legal_content_owner', 'text', array('max_length' => 100))
        ->add('legal_uploader', 'text', array('read_only' => true, 'max_length' => 100))
        /*->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true, 'disabled' => true))*/
        /*->add('file', 'file', array('required' => false))*/
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
                
                return $this->redirect($this->generateUrl('list'));
            }
        }
            return $this->render('UBCExamMainBundle:Default:update.html.twig', array('form' => $form->createView()));
    }
}
