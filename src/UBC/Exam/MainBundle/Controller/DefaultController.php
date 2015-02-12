<?php

namespace UBC\Exam\MainBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContextInterface;
use UBC\Exam\MainBundle\Entity\Exam;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     *
     * @Route("/", name="ubc_exam_main_homepage")
     */
    public function indexAction(Request $request)
    {
        $securityContext = $this->get('security.context');
        $isLoggedIn = $securityContext->isGranted('IS_AUTHENTICATED_FULLY');

        $em = $this->getDoctrine()->getManager();

        $coursesWithKeys = $request->getSession()->get('courses') ? $request->getSession()->get('courses') : array();
        $courses = array_keys($coursesWithKeys);
        $faculties = array_values(
            $em->getRepository('UBCExamMainBundle:SubjectFaculty')->getFacultiesByCourses($courses)
        );

        $userId = ($this->getUser() instanceof \UBC\Exam\MainBundle\Entity\User) ? $this->getUser()->getId() : 0;
        $uniqueSubjectCodes = $em->getRepository('UBCExamMainBundle:Exam')
            ->getAvailableSubjectCodes($userId, $faculties, $courses);

        $subjectCodeForTwig = array_combine(array_values($uniqueSubjectCodes), array_values($uniqueSubjectCodes));

        //ok, create a form to get user selection of exams
        $exam = new Exam();
        $formBuilder = $this->createFormBuilder($exam);
        $formBuilder->add('subject_code', 'choice', array('required' => false, 'label' => 'Type a course code to see matching courses', 'empty_value' => '- Choose course code -', 'choices' => $subjectCodeForTwig));
        $formBuilder->add('search', 'submit');
        $form = $formBuilder->getForm();


        $subjectCodeLabel = '';
        $subjectCode = '';
        $exams = array();

        if ($this->getRequest()->getMethod() == "POST") {
            $form->handleRequest($request);
            $subjectCodeLabel = $exam->getSubjectcode();
            $subjectCode = explode(' ', $subjectCodeLabel);
            $subjectCode = $subjectCode[0];
            $exams = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')
                ->findExamsByCourse($exam->getSubjectcode(), $userId, $faculties, $courses);

        }
        // TODO move to a listener
        $this->updateuser();

        return $this->render('UBCExamMainBundle:Default:index.html.twig', array(
            'form' => $form->createView(),
            'exams' => $exams,
            'subjectCodeLabel' => $subjectCodeLabel,
            'subjectCode' => $subjectCode
        ));
    }

    /**
     * Handles upload page
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/upload", name="exam_upload")
     */
    public function uploadAction(Request $request)
    {
        $exam = new Exam();
        $exam->setYear(date('Y'));
        $exam->setLegalDate(new \DateTime(date('Y-m-d')));

        //get faculties and subject code
        $em = $this->getDoctrine()->getManager();
        list($faculties, $subjectCode) = $em->getRepository('UBCExamMainBundle:SubjectFaculty')->getFacultySubjectCode();

        //get user
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof \UBC\Exam\MainBundle\Entity\User) {
            $fname = $user->getFirstname();
            $lname = $user->getLastname();
        } else {
            $fname = '';
            $lname = '';
        }
        $first = empty($fname) ? null : $user->getFirstname().' ';
        $last = empty($lname) ? null : $user->getLastname();

        $form = $this->createFormBuilder($exam);

        $form->add('campus', 'choice', array('empty_value' => '- Choose campus -','choices' => array('UBC' => 'Vancouver', 'UBCO' => 'Okanagan')));

        $form->add('faculty', 'text', array('max_length' => 50));
        $form->add('dept', 'text', array('max_length' => 10));

        if (count($subjectCode) > 1) {
            $form->add('subject_code', 'choice', array('empty_value' => '- Choose campus first -', 'choices' => $subjectCode))
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

                return $this->redirect($this->generateUrl('exam_list'));
            }
        }

        $this->updateuser();

        return $this->render('UBCExamMainBundle:Default:upload.html.twig', array('form' => $form->createView()));
    }

    /**
     * page for listing exams the person has uploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list", name="exam_list")
     */
    public function listAction()
    {
        $exams = array();
        $user = $this->get('security.context')->getToken()->getUser();

        if ($user instanceof \UBC\Exam\MainBundle\Entity\User) {
            $repo = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam');

            $query = $repo->createQueryBuilder('e')
                ->where('e.uploaded_by = :user')
                ->setParameter('user', $user)
                ->orderBy('e.created', 'DESC')
                ->getQuery();
            $exams = $query->getResult();
        }

        $this->updateuser();

        return $this->render('UBCExamMainBundle:Default:list.html.twig', array('exams' => $exams, 'username' => $user->getUsername()));
    }

    /**
     * if you type in login url, it will redirect to main exam page.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/login", name="exam_login")
     * @Route("/logout", name="exam_logout")
     */
    public function loginAction(Request $request)
    {
        $securityContext = $this->container->get('security.context');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            // authenticated REMEMBERED, FULLY will imply REMEMBERED (NON anonymous)
            return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
        }

        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContextInterface::AUTHENTICATION_ERROR
            );
        } elseif (null !== $session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
            $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContextInterface::LAST_USERNAME);

        return $this->render(
            'UBCExamMainBundle:Default:login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * deletes exam.
     *
     * @param integer $examID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/delete_exam/{examID}", name="exam_delete", requirements={"examID": "\d+"})
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

        return $this->redirect($this->generateUrl('exam_list'));
    }

    /**
     * allows uploader to update exam
     *
     * @param unknown $examID
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/update_exam/{examID}", name="exam_update", requirements={"examID": "\d+"})
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
        ->add('campus', 'choice', array('choices' => array('UBC' => 'Vancouver', 'UBCO' => 'Okanagan')))
        ->add('faculty', 'text', array('max_length' => 50))
        ->add('dept', 'text', array('max_length' => 50))
        ->add('subject_code', 'text', array('max_length' => 10))
        ->add('comments', 'textarea', array('required' => false))
        ->add('year')
        ->add('term', 'choice', array('choices' => array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD')))
        ->add('cross_listed', 'text', array('required' => false, 'max_length' => 10))
        ->add('access_level', 'choice', array('choices' => Exam::$ACCESS_LEVELS))
        ->add('type', 'choice', array('empty_value' => '- Choose type of material -', 'choices' => Exam::$TYPES))
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

                // log to upload log channel
                $uploadLogger = $this->get("monolog.logger.upload");
                $uploadLogger->info(join(',', array(
                    $exam->getCampus(),
                    $exam->getFaculty(),
                    $exam->getDept(),
                    $exam->getSubjectCode(),
                    $exam->getYear(),
                    $exam->getTerm(),
                    $exam->getType(),
                    $exam->getUploadedBy()->getUsername(),
                    $exam->getLegalContentOwner(),
                    $exam->getLegalUploader(),
                    Exam::$ACCESS_LEVELS[$exam->getAccessLevel()],
                    $exam->getPath(),
                )));

                return $this->redirect($this->generateUrl('exam_list'));
            }
        }
        return $this->render('UBCExamMainBundle:Default:update.html.twig', array('form' => $form->createView()));
    }

    /**
     * Function that provides download functionality based on filename and user permissions
     *
     * @param String $filename
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/download/{filename}", name="exam_download")
     */
    public function downloadAction($filename)
    {
        //try to get exam based on filename
        $exam = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')
            ->findExamByPath($filename);

        if (empty($exam)) {
            //flash message to show lack of permission
            $this->get('session')->getFlashBag()->add(
                'notice',
                'Either the exam does not exist or you do not have access to the exam'
            );

            return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
        }

        $accessLogger = $this->get("monolog.logger.access");
        $user = $this->get('security.context')->getToken()->getUser();
        if ($user instanceof \UBC\Exam\MainBundle\Entity\User ||
            $user instanceof \Symfony\Component\Security\Core\User\User
        ) {
            $username = $user->getUsername();
        } else {
            $username = $user;
        }

        $uploader = 'N/A';
        // try to load the upload user. If the user is deleted, we just ignore it
        try {
            $uploader = $exam->getUploadedBy()->getUsername();
        } catch (EntityNotFoundException $e) {
        }
        $accessLogger->info(join(',', array(
            $username,
            $exam->getCampus(),
            $exam->getFaculty(),
            $exam->getDept(),
            $exam->getSubjectCode(),
            $exam->getYear(),
            $exam->getTerm(),
            $exam->getType(),
            $uploader,
            $exam->getLegalContentOwner(),
            $exam->getLegalUploader(),
            Exam::$ACCESS_LEVELS[$exam->getAccessLevel()],
            $exam->getPath(),
        )));

        return $this->downloadPDF($exam);
    }

    /**
     * @param $subject string the course subject code
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/wikicontent/{subject}", name="exam_wiki", methods={"GET"})
     */
    public function getWikiContentAction($subject)
    {
        $logger = $this->get('logger');
        $wikiBaseUrl = $this->container->getParameter('wiki_base_url');
        $subjectCodes = $this->getDoctrine()
            ->getRepository('UBCExamMainBundle:SubjectFaculty')
            ->findBy(array('code' => $subject));

        $browser = $this->get('buzz');
        $content = array();
        foreach($subjectCodes as $code) {
            $title = sprintf('%s/%s/%s',
                $code->getCampus(), $code->getFaculty(), $code->getDepartment());
            $logger->debug('Getting wiki content from ' . $wikiBaseUrl.urlencode($title));
            $response = $browser->get($wikiBaseUrl.urlencode($title));
            // only take the content from HTTP 200, ignore others
            if ($response->getStatusCode() == 200) {
                $content[] = $response->getContent();
            }
        }

//        if (empty($content)) {
//            throw $this->createNotFoundException('No wiki content available for subject ' . $subject);
//        }

        return new Response(join('<hr>', $content));
    }

    /**
     * Get a list of subject codes by campus
     *
     * @param $campus string campus code, UBC or UBCO
     * @return JsonResponse list of subject codes in json format
     *
     * @Route("/subjectcode/{campus}", name="exam_campus", methods={"GET"})
     */
    public function getSubjectCodes($campus)
    {
        $subjectCodes = $this->getDoctrine()
            ->getRepository('UBCExamMainBundle:SubjectFaculty')
            ->getSubjectCodeArrayByCampus($campus);

        return new JsonResponse(array(
            'data' => $subjectCodes
        ));
    }

    /**
     * Get one subject code details by campus and code
     *
     * @param $campus string campus code, UBC or UBCO
     * @param $subjectCode string the subject code, e.g. CHIN
     * @return JsonResponse A json object contains subject code details
     *
     * @Route("/subjectcode/{campus}/{subjectCode}", name="exam_subjectcode", methods={"GET"})
     */
    public function getSubjectCode($campus, $subjectCode)
    {
        $subjectCode =  $this->getDoctrine()
            ->getRepository('UBCExamMainBundle:SubjectFaculty')
            ->getSubjectCodeByCampusAndCode($campus, $subjectCode);

        return new JsonResponse($subjectCode);
    }


    /**
     * Handles log manage page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/log", name="exam_log")
     */
    public function logAction()
    {
        return $this->render('UBCExamMainBundle:Default:log.html.twig');
    }

    /**
     * Handles log downloads
     *
     * @param string $type type of log to download
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/log/download/{type}", name="exam_log_download", )
     */
    public function logDownloadAction($type)
    {
        $header = array(
            'upload' => array(
                'timestamp',
                'campus',
                'faculty',
                'department',
                'subject code',
                'year',
                'term',
                'type',
                'uploaded by',
                'legal content owner',
                'legal uploader',
                'access level',
                'file',
            ),
            'access' => array(
                'timestamp',
                'user',
                'campus',
                'faculty',
                'department',
                'subject code',
                'year',
                'term',
                'type',
                'uploaded by',
                'legal content owner',
                'legal uploader',
                'access level',
                'file',
            )
        );
        if (!in_array($type, array('upload', 'access'))) {
            throw $this->createNotFoundException("$type log is not found.");
        }

        //get exam file path
        $filename = $this->container->getParameter('kernel.logs_dir')."/$type.log";
        $logContent = file_get_contents($filename);

        $logContent = preg_replace('/\[(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2})\] (access|upload)\.INFO: /', '$1,', $logContent);
        $logContent = str_replace(' [] []', '', $logContent);
        $logContent = join(',', $header[$type]) . "\n" . $logContent;

        // append file name with .csv
        $download_filename = basename($filename . '.csv');

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($filename)) . 'GMT');
        $response->headers->set('Accept-Ranges', 'byte');
        $response->headers->set('Content-Encoding', 'none');
        $response->headers->set('Content-type', mime_content_type($filename));
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $download_filename . '";');
        $response->setContent($logContent);

        return $response;
    }

    /**
     * private function to just encapsulate downloading pdf so that we don't repeat code
     *
     * @param Exam $exam
     *
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
     * TODO move it into a listener
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
            $userPuid = '';
            if ($user instanceof \UBC\Exam\MainBundle\Entity\User) {
                $userPuid = $user->getPuid();
            }

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
}
