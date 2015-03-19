<?php


namespace UBC\Exam\MainBundle\Entity;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ExamRepository extends EntityRepository
{
    private function addVisibleExamCriteria(QueryBuilder $qb, $user_id = 0, $faculties = array(), $courses = array())
    {
        // user_id = -1 means admin user, no additional filter needed.
        if ($user_id == -1) {
            return $qb;
        }

        $exp = $qb->expr()->eq('e.access_level', Exam::ACCESS_LEVEL_EVERYONE);

        if ($user_id != 0) {
            // logged in user access
            $exp = $qb->expr()->orX(
                $exp,
                $qb->expr()->eq('e.access_level', Exam::ACCESS_LEVEL_CWL)
            );

            if (!empty($faculties)) {
                $exp = $qb->expr()->orX(
                    $exp,
                    $qb->expr()->andX(
                        $qb->expr()->eq('e.access_level', Exam::ACCESS_LEVEL_FACULTY), // faculty level
                        $qb->expr()->in('e.faculty', $faculties)
                    )
                );
            }

            if (!empty($courses)) {
                $exp = $qb->expr()->orX(
                    $exp,
                    $qb->expr()->andX(
                        $qb->expr()->eq('e.access_level', Exam::ACCESS_LEVEL_COURSE), // course level
                        $qb->expr()->in('e.subject_code', $courses)
                    )
                );
            }

            $exp = $qb->expr()->orX(
                $exp,
                $qb->expr()->eq('e.uploaded_by', $user_id)
            );
        }

        return $qb->andWhere($exp);
    }

    private function addEditableExamCriteria(QueryBuilder $qb, $user_id = 0)
    {
        if ($user_id == -1) {
            // -1 means admin user, no additional filter needed.
            return $qb;
        } else if ($user_id == 0) {
            // 0 means unauthenticated user, no result should be returned.
            return $qb->andWhere('1 = 0');
        } else {
            return $qb->andWhere("e.uploaded_by = $user_id");
        }
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
            ->orWhere('e.cross_listed LIKE :course')
            ->orderBy('e.year', 'DESC')
            ->setParameter('course', '%'.trim($course).'%');

        $qb = $this->addVisibleExamCriteria($qb, $user_id, $faculties, $courses);

        $query = $qb->getQuery();

        // TODO add cache
        return $query->getResult();
    }

    public function findExamsByIds($ids, $user_id, $faculties = array(), $courses = array()) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e')
            ->from('UBCExamMainBundle:Exam', 'e')
            ->where('e.id IN (:ids)')
            ->orderBy('e.year', 'DESC')
            ->setParameter('ids', $ids);

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

    public function findEditableExamById($id, $user_id = 0) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e')
            ->from('UBCExamMainBundle:Exam', 'e')
            ->where('e.id = :id')
            ->setParameter('id', $id);

        $qb = $this->addEditableExamCriteria($qb, $user_id);

        $query = $qb->getQuery();

        // TODO add cache
        return $query->getOneOrNullResult();
    }

    public function findAllEditableExams($user_id = 0) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e')
            ->from('UBCExamMainBundle:Exam', 'e')
            ->orderBy('e.created', 'DESC');

        $qb = $this->addEditableExamCriteria($qb, $user_id);

        $query = $qb->getQuery();

        // TODO add cache
        return $query->getResult();
    }
    /**
     * Generate statistics by faculty.
     *
     * @return array statistics of the db with the following format:
     * array(
     *   array(
     *     campus => 'UBC',
     *     faculty => 'facutly1',
     *     uploads => 1,
     *     downloads => 20,
     *   ),
     * )
     */
    public function getExamStats()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('e.campus, e.faculty, count(e.id) AS uploads, sum(e.downloads) AS downloads')
            ->from('UBCExamMainBundle:Exam', 'e')
            ->groupBy('e.campus, e.faculty');

        return $qb->getQuery()->getResult();
    }
} 