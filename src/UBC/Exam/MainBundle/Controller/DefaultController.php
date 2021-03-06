<?php

namespace UBC\Exam\MainBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrapView;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use UBC\Exam\MainBundle\Entity\Exam;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UBC\Exam\MainBundle\Form\ExamFilterType;
use ZendSearch\Lucene\Search\Query\Wildcard;
use ZendSearch\Lucene\Search\QueryParser;

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
        $pagination = array();
        $pagerHtml = null;

        $q = $request->get('q');

        if (!is_null($q) && !empty($q)) {
            // search the index
            QueryParser::setDefaultOperator(QueryParser::B_AND);
            Wildcard::setMinPrefixLength(1);
            $hits = $this->get('ivory_lucene_search')->getIndex('exams')->find($q.'*');

            $ids = array();
            foreach ($hits as $hit) {
                $ids[] = $hit->pk;
            }
            $ids = array_unique($ids);

            // search the db by ids, because we need to get the exams only visible for current user
            if (!empty($ids)) {
                // find out the current user registered courses and faculty
                $em = $this->getDoctrine()->getManager();
                $coursesWithKeys = $request->getSession()->get('courses') ? $request->getSession()->get('courses') : array();
                $courses = array_keys($coursesWithKeys);
                $faculties = array_values(
                    $em->getRepository('UBCExamMainBundle:SubjectFaculty')->getFacultiesByCourses($courses)
                );

                $userId = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? -1 : $this->getCurrentUserId();

                $qb = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')
                    ->queryExamsByIds($ids, $userId, $faculties, $courses);

                $paginator  = $this->get('knp_paginator');
                $pagination = $paginator->paginate(
                    $qb,
                    $request->query->get('page', 1)/*page number*/,
                    20/*limit per page*/
                );

            }
        }

        return $this->render('UBCExamMainBundle:Default:index.html.twig', array(
            'pagination' => $pagination,
            'q' => $q,
            'subjectCode' => '', // used by wiki content
            'subjectCodeLabel' => '', // used by wiki content
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
        $user = $this->get('security.token_storage')->getToken()->getUser();
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

        $form->add('campus', 'choice', array(
            'placeholder' => '- Choose campus -',
            'choices' => Exam::$CAMPUSES,
            'choices_as_values' => true
        ));

        $form->add('faculty', 'text', array('max_length' => 50));
        $form->add('dept', 'text', array('max_length' => 10));

        if (count($subjectCode) > 1) {
            $form->add('subject_code', 'choice', array(
                'placeholder' => '- Choose campus first -',
                'choices' => array_flip($subjectCode),
                'choices_as_values' => true
                ))
                 ->add('subject_code_number', 'text', array('label' => false, 'mapped' => false, 'max_length' => 5));  //extra field to
        } else {
            $form->add('subject_code', 'text', array('max_length' => 10));
        }

        $form->add('comments', 'textarea', array('required' => false))
            ->add('year')
            ->add('term', 'choice', array('placeholder' => '- Choose term -', 'choices' => array_flip(Exam::$TERMS), 'choices_as_values' => true))
            ->add('cross_listed', 'text', array('required' => false, 'max_length' => 10))
            ->add('access_level', 'choice', array('placeholder' => '- Choose access level -', 'choices' => array_flip(Exam::$ACCESS_LEVELS), 'choices_as_values' => true))
            ->add('type', 'choice', array('placeholder' => '- Choose type of material -', 'choices' => array_flip(Exam::$TYPES), 'choices_as_values' => true))
            ->add('legal_date', 'date', array('widget' => 'single_text', 'attr' => array('read_only' => true)))
            ->add('legal_content_owner', 'text', array('max_length' => 100))
            ->add('legal_uploader', 'text', array('data' => $first.$last, 'max_length' => 100))
            ->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true))
            ->add('file', 'file')
            ->add('upload', 'submit');

        $form = $form->getForm();

        if ($request->getMethod() == "POST") {
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
                $user = $this->get('security.token_storage')->getToken()->getUser();
                // if in_memory user is used to login, we manually find the first user in db
                if ($user instanceof \Symfony\Component\Security\Core\User\User) {
                    $user = $this->getDoctrine()->getRepository('UBCExamMainBundle:User')
                        ->findOneBy(array());
                }
                $exam->setUploadedBy($user);

                //save to DB
                $em->persist($exam);
                $em->flush();

                return $this->redirect($this->generateUrl('exam_list'));
            }
        }

        return $this->render('UBCExamMainBundle:Default:upload.html.twig', array('form' => $form->createView()));
    }

    /**
     * page for listing exams the person has uploaded
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list", name="exam_list")
     */
    public function listAction(Request $request)
    {
        $userId = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? -1 : $this->getCurrentUserId();
        $qb = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')->queryAllEditableExams($userId);
//        $paginator  = $this->get('knp_paginator');
//        $pagination = $paginator->paginate(
//            $qb,
//            $request->query->get('page', 1)/*page number*/,
//            10/*limit per page*/
//        );
        list($filterForm, $qb) = $this->filter($qb, $request);

        list($entities, $pagerHtml) = $this->paginator($qb, $request);

        return $this->render('UBCExamMainBundle:Default:list.html.twig', array(
            'entities' => $entities,
            'pagerHtml' => $pagerHtml,
            'filterForm' => $filterForm->createView(),
        ));
    }

    /**
     * Create filter form and process filter request.
     * @param QueryBuilder $queryBuilder
     * @return array
     */
    protected function filter(QueryBuilder $queryBuilder, Request $request)
    {
        $session = $request->getSession();
        $filterForm = $this->createForm(new ExamFilterType());

        // Reset filter
        if ($request->get('filter_action') == 'reset') {
            $session->remove('ExamControllerFilter');
        }

        // Filter action
        if ($request->get('filter_action') == 'filter') {
            // Bind values from the request
            $filterForm->bind($request);

            if ($filterForm->isValid()) {
                // Build the query from the given form object
                $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($filterForm, $queryBuilder);
                // Save filter to session
                $filterData = $filterForm->getData();
                $session->set('ExamControllerFilter', $filterData);
            }
        } else {
            // Get filter from session
            if ($session->has('ExamControllerFilter')) {
                $filterData = $session->get('ExamControllerFilter');
                $filterForm = $this->createForm(new ExamFilterType(), $filterData);
                $this->get('lexik_form_filter.query_builder_updater')->addFilterConditions($filterForm, $queryBuilder);
            }
        }

        return array($filterForm, $queryBuilder);
    }

    /**
     * Get results from paginator and get paginator view.
     *
     */
    protected function paginator($queryBuilder, Request $request)
    {
        // Paginator
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $currentPage = $request->get('page', 1);
        $pagerfanta->setCurrentPage($currentPage);
        $entities = $pagerfanta->getCurrentPageResults();

        // Paginator - route generator
        $me = $this;
        $routeGenerator = function($page) use ($me)
        {
            return $me->generateUrl('exam_list', array('page' => $page));
        };

        // Paginator - view
        $translator = $this->get('translator');
        $view = new TwitterBootstrapView();
        $pagerHtml = $view->render($pagerfanta, $routeGenerator, array(
            'proximity' => 3,
            'prev_message' => '← Previous',
            'next_message' => 'Next →',
        ));

        return array($entities, $pagerHtml);
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
        $securityContext = $this->container->get('security.authorization_checker');
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
        $userId = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? -1 : $this->getCurrentUserId();
        $exam = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')
            ->findEditableExamById($examID, $userId);

        if (is_null($exam)) {
            throw $this->createNotFoundException('No exam exists for that user or you do not have access to this exam.');
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
     * @param integer $examID
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
        $userId = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? -1 : $this->getCurrentUserId();
        $exam = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')
            ->findEditableExamById($examID, $userId);

        if (is_null($exam)) {
            throw $this->createNotFoundException('No exam exists for that user or you do not have access to this exam.');
        }

        //ok, create update form!
        $form = $this->createFormBuilder($exam)
            ->add('campus', 'choice', array('choices' => Exam::$CAMPUSES, 'choices_as_values' => true))
            ->add('faculty', 'text', array('max_length' => 50))
            ->add('dept', 'text', array('max_length' => 50))
            ->add('subject_code', 'text', array('max_length' => 10))
            ->add('comments', 'textarea', array('required' => false))
            ->add('year')
            ->add('term', 'choice', array('choices' => array_flip(Exam::$TERMS), 'choices_as_values' => true))
            ->add('cross_listed', 'text', array('required' => false, 'max_length' => 10))
            ->add('access_level', 'choice', array('choices' => array_flip(Exam::$ACCESS_LEVELS), 'choices_as_values' => true))
            ->add('type', 'choice', array('placeholder' => '- Choose type of material -', 'choices' => array_flip(Exam::$TYPES), 'choices_as_values' => true))
            ->add('legal_date', 'date', array('widget' => 'single_text', 'attr' => array('read_only' => true)))
            ->add('legal_content_owner', 'text', array('max_length' => 100))
            ->add('legal_uploader', 'text', array('max_length' => 100, 'attr' => array('read_only' => true)))
            /*->add('legal_agreed', 'checkbox', array('label' => 'I agree', 'required' => true, 'disabled' => true))*/
            /*->add('file', 'file', array('required' => false))*/
            ->add('upload', 'submit')
            ->getForm();

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                //setup who did it!
                $user = $this->get('security.token_storage')->getToken()->getUser();
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

                $this->get('session')->getFlashBag()->add('success', 'Update successful!');
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
        $userId = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') ? -1 : $this->getCurrentUserId();

        //try to get exam based on filename
        $exam = $this->getDoctrine()->getRepository('UBCExamMainBundle:Exam')
            ->findExamByPath($filename, $userId);

        if (empty($exam)) {
            //flash message to show lack of permission
            $this->get('session')->getFlashBag()->add(
                'notice',
                'Either the exam does not exist or you do not have access to the exam'
            );

            return $this->redirect($this->generateUrl('ubc_exam_main_homepage'));
        }

        $accessLogger = $this->get("monolog.logger.access");
        $user = $this->get('security.token_storage')->getToken()->getUser();
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
     * @param $campus  string the campus code
     * @param $subject string the course subject code
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/wikicontent/{campus}/{subject}", name="exam_wiki", methods={"GET"})
     */
    public function getWikiContentAction($campus, $subject)
    {
        $logger = $this->get('logger');

        $subjectCode = $this->getDoctrine()
            ->getRepository('UBCExamMainBundle:SubjectFaculty')
            ->findOneBy(array('campus' => $campus, 'code' => $subject));

        // loading wiki contents from cache first
        $cache = $this->get('doctrine_cache.providers.wiki_content');
        $content = $cache->fetch($subjectCode->getCacheKey());
        if ($content) {
            $logger->debug('Getting wiki content from cache');
            return new Response($content);
        }

        $wikiBaseUrl = $this->container->getParameter('wiki_base_url');

        $browser = $this->get('buzz');
        $content = array();
        $title = sprintf('%s/%s/%s',
            $subjectCode->getCampus(), $subjectCode->getFaculty(), $subjectCode->getDepartment());
        $logger->debug('Getting wiki content from ' . $wikiBaseUrl.urlencode($title));
        $response = $browser->get($wikiBaseUrl.urlencode($title));
        // only take the content from HTTP 200, ignore others
        if ($response->getStatusCode() == 200) {
            $content = $response->getContent();
        }

        $cache->save($subjectCode->getCacheKey(), $content, $this->container->getParameter('wiki_cache_lifetime'));

//        if (empty($content)) {
//            throw $this->createNotFoundException('No wiki content available for subject ' . $subject);
//        }

        return new Response($content);
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
        $stats = $this->getDoctrine()
            ->getRepository('UBCExamMainBundle:Exam')
            ->getExamStats();
        return $this->render('UBCExamMainBundle:Default:log.html.twig', array(
            'stats' => $stats
        ));
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
     * @param $currentRoute
     *
     * @return Response
     *
     * @Cache(smaxage=60)
     * @Route("/nav/{currentRoute}", name="exam_nav")
     */
    public function getNavBarAction($currentRoute) {
        return $this->render(
            '@UBCExamMain/Default/nav.html.twig',
            array('currentRoute' => $currentRoute)
        );
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
        $filename = $exam->getAbsolutePath($this->container->getParameter('upload_dir'));
        if (!$filename) {
            throw new FileNotFoundException($exam->getPath());
        }

        // Generate response
        $response = new Response();

        // Set headers
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($filename)) . 'GMT');
        $response->headers->set('Accept-Ranges', 'byte');
        $response->headers->set('Content-Encoding', 'none');
        $response->headers->set('Content-type', mime_content_type($filename));
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $exam->getUserFilename() . '";');

        // Send headers before outputting anything
        $response->sendHeaders();

        $response->setContent(file_get_contents($filename));

        $exam->increaseDownloads();
        $this->getDoctrine()->getManager()->flush();

        return $response;
    }

    private function getCurrentUserId()
    {
        return ($this->getUser() instanceof \UBC\Exam\MainBundle\Entity\User) ? $this->getUser()->getId() : 0;
    }
}
