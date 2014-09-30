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
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function indexAction(Request $request)
    {
        $securityContext = $this->get('security.context');
        $isLoggedIn = $securityContext->isGranted('IS_AUTHENTICATED_FULLY');
        
        //ok, create a form to get user selection of exams
        $em = $this->getDoctrine()->getManager();
        list($faculties, $subjectCode) = $this->getFacultySubjectCode($em);
        
        $exam = new Exam();
        
        $form = $this->createFormBuilder($exam)
            ->add('year', 'text', array('required' => false))
            ->add('term', 'choice', array('required' => false, 'empty_value' => '- Choose term -', 'choices' => Exam::$TERMS))
            ->add('legal_content_owner', 'text', array('required' => false, 'max_length' => 100));
        
        if (count($subjectCode) > 1) {
            $form->add('subject_code', 'choice', array('required' => false, 'empty_value' => '- Choose subject -', 'choices' => $subjectCode))
                ->add('subject_code_number', 'text', array('required' => false, 'label' => false, 'mapped' => false, 'max_length' => 5));   //extra field to split up code form number
        } else {
            $form->add('subject_code', 'text', array('required' => false, 'max_length' => 10));
        }
        
        if (count($faculties) > 1) {
            $form->add('faculty', 'choice', array('required' => false, 'empty_value' => '- Choose faculty -','choices' => $faculties));
        } else {
            $form->add('faculty', 'text', array('required' => false, 'max_length' => 50));
        }
        $form->add('search', 'submit');
        $form->add('reset', 'reset');
        
        $form = $form->getForm();
        
        //setup so we can get a list of exams to show
        $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');
        $query = $repo->createQueryBuilder('e');
        
        if ($this->getRequest()->getMethod() == "POST") {
            $form->handleRequest($request);
            $formSubjectCodeNumber = $exam->getSubjectcode();
        
            //need try/catch so that it doesn't puke if subject_code_number doesn't exist
            if ($form->has('subject_code_number')) {
                $combinedCode = $exam->getSubjectcode().' '.trim($form->get('subject_code_number')->getData());
                if (strlen($combinedCode) > 3) {
                    $formSubjectCodeNumber = trim($combinedCode);
                }
            }
            
            $exam->setSubjectcode($formSubjectCodeNumber);
            
            //setup query based on exam return stuff
            $query = $query->where('1=1');  //just a place holder so that we can use "or" conditions on the rest
            
            $yearParameter = trim($exam->getYear());
            if (!empty($yearParameter)) {
                $query = $query->orWhere('e.year = :year')
                    ->setParameter('year', $yearParameter);
            }
            
            $termParameter = trim($exam->getTerm());
            if (!empty($termParameter)) {
                $query = $query->orWhere('e.term = :term')
                    ->setParameter('term', $termParameter);
            }
            
            $legalContentOwnerParameter = trim($exam->getLegalContentOwner());
            if (!empty($legalContentOwnerParameter)) {
                //want to say thanks to http://stackoverflow.com/questions/2843009/how-to-escape-like-var-with-doctrine
                $legalContentOwnerParameter = addcslashes($lcoParam, "%_");
                $query = $query->orWhere($query->expr()->like('e.legal_content_owner', ':legalContentOwner'))
                    ->setParameter('legalContentOwner', '%'.$legalContentOwnerParameter.'%');
            }
            
            $subjectCodeParameter = trim($exam->getSubjectcode());
            if (!empty($subjectCodeParameter)) {
                $subjectCodeParameter = addcslashes($subjectCodeParameter, "%_");
                $query = $query->orWhere($query->expr()->like('e.subject_code', ':subjectCodeParameter'))
                    ->setParameter('subjectCodeParameter', '%'.$subjectCodeParameter.'%');
            }
            
            $facultyParameter = trim($exam->getFaculty());
            if (!empty($facultyParameter)) {
                $query = $query->orWhere('e.faculty = :faculty')
                    ->setParameter('faculty', $facultyParameter);
            }
        } else {
            $query = $query->orderBy('e.created', 'DESC');
        }
        $query = $query->getQuery();
        $exams = $query->getResult();
        
        $this->updateuser();
        
        return $this->render('UBCExamMainBundle:Default:index.html.twig', array('form' => $form->createView(), 'isLoggedIn' => $isLoggedIn, 'exams' => $exams));
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
        
        //get faculties and subject code
        $em = $this->getDoctrine()->getManager();
        list($faculties, $subjectCode) = $this->getFacultySubjectCode($em);
        
        //get user
        $user = $this->get('security.context')->getToken()->getUser();
        $fname = $user->getFirstname();
        $lname = $user->getLastname();
        $first = empty($fname) ? null : $user->getFirstname().' ';
        $last = empty($lname) ? null : $user->getLastname();
        
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
            ->add('term', 'choice', array('empty_value' => '- Choose term -', 'choices' => Exam::$TERMS))
            ->add('cross_listed', 'text', array('required' => false, 'max_length' => 10))
            ->add('access_level', 'choice', array('empty_value' => '- Choose access level -', 'choices' => Exam::$ACCESS_LEVELS))
            ->add('type', 'choice', array('empty_value' => '- Choose type of material -', 'choices' => Exam::$TYPES))
            ->add('legal_date', 'date', array('widget' => 'single_text', 'read_only' => true))
            ->add('legal_content_owner', 'text', array('max_length' => 100))
            ->add('legal_uploader', 'text', array('data' => $first.$last, 'max_length' => 100))
            ->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true))
            ->add('file', 'file')
            ->add('upload', 'submit');
        
        $form = $form->getForm();

        if ($this->getRequest()->getMethod() == "POST") {
            $form->handleRequest($request);
            $formSubjectCodeNumber = $exam->getSubjectcode();
            
            //need try/catch so that it doesn't puke if subject_code_number doesn't exist
            if ($form->has('subject_code_number')) {
                $combinedCode = $exam->getSubjectcode().' '.trim($form->get('subject_code_number')->getData());
                if (strlen($combinedCode) > 5) {
                    $formSubjectCodeNumber = $combinedCode;
                }
            }
            
            $exam->setSubjectcode($formSubjectCodeNumber);
            
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
        
        $this->updateuser();
        
        return $this->render('UBCExamMainBundle:Default:upload.html.twig', array('form' => $form->createView()));
    }
    
    /**
     * page for listing exams the person has uploaded
     * 
     * @param Request $request
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
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
        
        $this->updateuser();
        
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
    public function refreshAction()
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
            $em = $this->getDoctrine()->getManager();
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
        $fileCache = $this->container->get('twig')->getCacheFilename('UBCExamMainBundle::layout.html.twig');
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
        $em = $this->getDoctrine()->getManager();
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

    /**
     * 
     * 
     * @param unknown $filename
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadAction($filename)
    {
        $return_val = new Response('still work in progress');

        //try to get exam based on filename
        $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');
        $exam = $repo->findOneByPath($filename);

        $securityContext = $this->get('security.context');
        $user = $securityContext->getToken()->getUser();
    
        //get what user permissions are for various departments/etc and depends on
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            //User is logged in, so need to check permissions to download
            //but for now, we can just let them have it.
            $return_val = $this->downloadPDF($exam);    //need to remove and add logic to check permissions!
        } else {
            //user should ONLY be able to see public exams since he is NOT logged in and can't check more stuff
            if (empty($exam) || $exam->getAccessLevelString() != 'Everyone') {
                //flash message to show lack of permission
                $this->get('session')->getFlashBag()->add(
                    'notice',
                    'Either the exam does not exist or you do not have access to the exam'
                );
                $return_val = $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
            } else {
                $return_val = $this->downloadPDF($exam);
            }
        }

        return $return_val;
    }
    
    /**
     * private function to just encapsulate downloading pdf so that we don't repeat code
     * 
     * @param unknown $exam
     * @return boolean|\Symfony\Component\HttpFoundation\Response
     */
    private function downloadPDF($exam)
    {
        if (!($exam instanceof \UBC\Exam\MainBundle\Entity\Exam)) {
            return false;
        } 
        
        //get exam file path
        $filename = realpath($exam->getAbsolutePath());
        
        // Generate response
        $response = new Response();
        
        // Set headers
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($filename)) . 'GMT');
        $response->headers->set('Accept-Ranges', 'byte');
        $response->headers->set('Content-Encoding', 'none');
        $response->headers->set('Content-type', mime_content_type($filename));
        $response->headers->set('Content-Disposition', 'attachment;filename="' . basename($filename) . '";');
        
        // Send headers before outputting anything
        $response->sendHeaders();
        
        $response->setContent(file_get_contents($filename));
        
        return $response;
    }

    /**
     * checks and updates user profile if puid not set
     * 
     * @return void
     */
    private function updateuser()
    {
        $securityContext = $this->get('security.context');
        
        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $securityToken = $securityContext->getToken();
            $user = $securityToken->getUser();
            $securityAttributes = $securityToken->getAttributes();
            $userPuid = $user->getPuid();
            
            //check if we need to update user to put in puid/first/lastname
            if (!empty($securityAttributes) && isset($securityAttributes['puid']) && empty($userPuid)) {
                $user->setPuid($securityAttributes['puid']);
                $user->setLastname($securityAttributes['sn']);
                $user->setFirstname($securityAttributes['givenName']);
                
                //save updated user info
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            }
        }
    }
    
    /**
     * function to pull list of faculties and subject codes (aka AANB, ENSC, etc) if available
     * 
     * @return array
     */
    private function getFacultySubjectCode($em)
    {
        $subjectFacultyQuery = $em->createQueryBuilder('s')
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
        
        return array($faculties, $subjectCode);
    }
}
