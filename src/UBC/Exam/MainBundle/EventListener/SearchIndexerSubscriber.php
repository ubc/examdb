<?php


namespace UBC\Exam\MainBundle\EventListener;


use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use UBC\Exam\MainBundle\Entity\Exam;
use ZendSearch\Lucene\Document;

class SearchIndexerSubscriber implements EventSubscriber {
    private $searchEngine;
    private $indexer;

    public function __construct($searchEngine) {
        $this->searchEngine = $searchEngine;
        $this->indexer = $this->searchEngine->getIndex('exams');
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postPersist',
            'postUpdate',
            'preRemove',
        );
    }

    public function postPersist(LifecycleEventArgs $args) {
        $exam = $args->getEntity();

        if ($exam instanceof Exam) {
            $exam->index($this->indexer);
        }
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $exam = $args->getEntity();

        if ($exam instanceof Exam) {
            // remove the old index
            $exam->deleteIndex($this->indexer);
            // reindex the new one
            $exam->index($this->indexer);
        }
    }

    public function preRemove(LifecycleEventArgs $args) {
        $exam = $args->getEntity();

        if ($exam instanceof Exam) {
            $exam->deleteIndex($this->indexer);
        }
    }
}
