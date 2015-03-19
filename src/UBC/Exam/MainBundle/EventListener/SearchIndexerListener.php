<?php


namespace UBC\Exam\MainBundle\EventListener;


use Doctrine\ORM\Event\LifecycleEventArgs;
use UBC\Exam\MainBundle\Entity\Exam;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;

class SearchIndexerListener {
    private $searchEngine;

    public function __construct($searchEngine) {
        $this->searchEngine= $searchEngine;
    }

    public function postPersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();

        if ($entity instanceof Exam) {
            $indexer = $this->searchEngine->getIndex('exams');
            $document = new Document();
            $document->addField(Field::keyword('course', $entity->getSubjectcode()));
            $document->addField(Field::keyword('cross-listed', $entity->getCrossListed()));
            $document->addField(Field::keyword('instructor', $entity->getLegalContentOwner()));
            $document->addField(Field::text('comments', $entity->getComments()));

            $indexer->addDocument($document);

            $indexer->commit();
            $indexer->optimize();
        }
    }
}