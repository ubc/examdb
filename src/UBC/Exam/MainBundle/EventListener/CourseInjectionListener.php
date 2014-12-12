<?php


namespace UBC\Exam\MainBundle\EventListener;


use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use UBC\SISAPI\Service\StudentService;

class CourseInjectionListener {
    protected $session;
    protected $service;

    public function __construct(Session $session, StudentService $service)
    {
        $this->session = $session;
        $this->service = $service;
    }

    public function onLoginSuccess(InteractiveLoginEvent $event)
    {
        $courses = array();
        $user = $event->getAuthenticationToken()->getUser();
        // ignore the in_memory user
        if ($user instanceof \UBC\Exam\MainBundle\Entity\User) {
            $id = $user->getPuid();

            if (!empty($id)) {
                // TODO don't throw exceptions on 404
                $sections = $this->service->getStudentCurrentSections($id);

                foreach($sections as $s) {
                    $key = $s->getCourse()->getCode() . ' ' . $s->getCourse()->getNumber();
                    $courses[$key] = $s->getCourse();
                }
            }
        }

        $this->session->set('courses', $courses);
    }
} 