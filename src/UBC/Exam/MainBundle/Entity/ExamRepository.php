<?php


namespace UBC\Exam\MainBundle\Entity;


use Doctrine\ORM\EntityRepository;

class ExamRepository extends EntityRepository{
    public function getAvailableSubjectCodes($user_id = 0, $faculties = array(), $courses = array()) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('e.subject_code'))
            ->from('UBCExamMainBundle:Exam', 'e')
            ->where('e.access_level = 1')
            ->groupBy('e.subject_code');

        if ($user_id != 0) {
            $qb->orWhere($qb->expr()->eq('e.access_level', 2)); // logged in user access

            if (!empty($faculties)) {
                $qb->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->eq('e.access_level', 3), // faculty level
                        $qb->expr()->in('e.faculty', $faculties)
                    )
                );
            }

            if (!empty($courses)) {
               $qb->orWhere(
                   $qb->expr()->andX(
                       $qb->expr()->eq('e.access_level', 4), // course level
                       $qb->expr()->in('e.subject_code', $courses)
                   )
               );
            }

            $qb->expr()->andX(
                $qb->expr()->eq('e.access_level', 5), // only me
                $qb->expr()->eq('e.uploaded_by', $user_id)
            );
        }

        $query = $qb->getQuery();
        $codes = $query->getResult();

        return array_map(
            function($c) {return $c['subject_code'];},
            $codes
        );

    }
} 