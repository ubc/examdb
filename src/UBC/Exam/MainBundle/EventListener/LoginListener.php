<?php


namespace UBC\Exam\MainBundle\EventListener;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener {
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function onLoginSuccess(InteractiveLoginEvent $event)
    {
        $entityManager = $this->doctrine->getManager();
        $user = $event->getAuthenticationToken()->getUser();
        $attributes = $event->getAuthenticationToken()->getAttributes();

        $userPuid = '';
        if ($user instanceof \UBC\Exam\MainBundle\Entity\User) {
            $userPuid = $user->getPuid();
        }

        //check if we need to update user to put in puid/first/lastname
        if (!empty($attributes) && isset($attributes['puid']) && empty($userPuid)) {
            $user->setPuid($attributes['puid']);
            $user->setLastname($attributes['sn']);
            $user->setFirstname($attributes['givenName']);
        }

        $user->setLastLogin(new \DateTime());
//            $em->persist($user);
        $entityManager->flush();
    }
}