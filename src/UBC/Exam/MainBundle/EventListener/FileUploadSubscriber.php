<?php


namespace UBC\Exam\MainBundle\EventListener;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use UBC\Exam\MainBundle\Entity\Exam;

/**
 * Class FileUploadListener
 * @package UBC\Exam\MainBundle\EventListener
 *
 * This listener integrates Doctrine lifecycle with file uploads. It also provides a way to inject upload directory.
 */
class FileUploadSubscriber implements EventSubscriber
{

    private $uploadDir;

    public function __construct($uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
            'postPersist',
            'postUpdate',
            'postRemove',
        );
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->preUpload($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->preUpload($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->upload($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->upload($args);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Exam) {
            $entity->removeUpload($this->uploadDir);
        }
    }

    public function preUpload(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Exam) {
            $entity->preUpload();
        }
    }

    public function upload(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Exam) {
            $entity->upload($this->uploadDir);
        }
    }

}