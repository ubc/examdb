<?php


namespace UBC\Exam\MainBundle\Entity;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ExamRepository extends EntityRepository
{
    private function addVisibleExamCriteria(QueryBuilder $qb, $user_id = 0, $faculties = array(), $courses = array())
    {
        $exp = $qb->expr()->eq('e.access_level', 1);

        if ($user_id != 0) {
            // logged in user access
            $exp = $qb->expr()->orX(
                $exp,
                $qb->expr()->eq('e.access_level', 2)
            );

            if (!empty($faculties)) {
                $exp = $qb->expr()->orX(
                    $exp,
                    $qb->expr()->andX(
                        $qb->expr()->eq('e.access_level', 3), // faculty level
                        $qb->expr()->in('e.faculty', $faculties)
                    )
                );
            }

            if (!empty($courses)) {
                $exp = $qb->expr()->orX(
                    $exp,
                    $qb->expr()->andX(
                        $qb->expr()->eq('e.access_level', 4), // course level
                        $qb->expr()->in('e.subject_code', $courses)
                    )
                );
            }

            $exp = $qb->expr()->orX(
                $exp,
                $qb->expr()->andX(
                    $qb->expr()->eq('e.access_level', 5), // only me
                    $qb->expr()->eq('e.uploaded_by', $user_id)
                )
            );
        }

        return $qb->andWhere($exp);
    }

    public function getAvailableSubjectCodes($user_id = 0, $faculties = array(), $courses = array())
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(array('e.subject_code'))
            ->from('UBCExamMainBundle:Exam', 'e')
            ->groupBy('e.subject_code');

        $qb = $this->addVisibleExamCriteria($qb, $user_id, $faculties, $courses);

        $query = $qb->getQuery();
        // TODO add cache
        $codes = $query->getResult();

        return array_map(
            function ($c) {
                return $c['subject_code'];
            },
            $codes
        );
    }

    public function findExamsByCourse($course, $user_id = 0, $faculties = array(), $courses = array())
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e')
            ->from('UBCExamMainBundle:Exam', 'e')
            ->where('e.subject_code LIKE :course')
            ->setParameter('course', trim($course));

        $qb = $this->addVisibleExamCriteria($qb, $user_id, $faculties, $courses);

        $query = $qb->getQuery();

        // TODO add cache
        return $query->getResult();
    }

    public function findExamByPath($path, $user_id = 0, $faculties = array(), $courses = array())
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e')
            ->from('UBCExamMainBundle:Exam', 'e')
            ->where('e.path LIKE :path')
            ->setParameter('path', trim($path));

        $qb = $this->addVisibleExamCriteria($qb, $user_id, $faculties, $courses);

        $query = $qb->getQuery();

        // TODO add cache
        return $query->getOneOrNullResult();
    }
} 