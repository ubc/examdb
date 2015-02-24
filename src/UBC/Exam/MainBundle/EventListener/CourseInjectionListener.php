<?php


namespace UBC\Exam\MainBundle\EventListener;


use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use UBC\LtCommons\Service\StudentService;

class CourseInjectionListener {
    protected $session;
    protected $service;
    protected $logger;

    public function __construct(Session $session, StudentService $service, $logger)
    {
        $this->session = $session;
        $this->service = $service;
        $this->logger = $logger;
    }

    public function onLoginSuccess(InteractiveLoginEvent $event)
    {
        $courses = array();
        $user = $event->getAuthenticationToken()->getUser();
        // ignore the in_memory user
        if ($user instanceof \UBC\Exam\MainBundle\Entity\User) {
            $id = $user->getPuid();

            if (!empty($id)) {
                try {
                    $sections = $this->service->getStudentCurrentSections($id);
                } catch (ClientException $e) {
                    // the user may not exists in SIS
                    if (404 == $e->getResponse()->getStatusCode()) {
                        $sections = array();
                    } else {
                        throw $e;
                    }
                } catch (\RuntimeException $e) {
                    $sections = array();
                    $this->logger->warn("Failed to load current section for $id. Reason: " . $e->getMessage());
                }

                foreach($sections as $s) {
                    $key = $s->getCourse()->getCode() . ' ' . $s->getCourse()->getNumber();
                    $courses[$key] = $s->getCourse();
                }
            }
        }

        $this->session->set('courses', $courses);
    }
} 