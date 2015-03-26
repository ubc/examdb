<?php

namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SubjectCodeRepository
 */
class SubjectCodeRepository extends EntityRepository
{
    public function refresh(array $codes)
    {
        //dump old subjectfaculty table
        $em = $this->getEntityManager();
        $cmd = $em->getClassMetadata('UBC\Exam\MainBundle\Entity\SubjectFaculty');
        $dbPlatform = $em->getConnection()->getDatabasePlatform();
        $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
        $em->getConnection()->executeUpdate($q);

        //insert new subjectfaculties
        foreach ($codes as $subjectFaculty) {
            $em->persist($subjectFaculty);
        }
        $em->flush();

        // flush cache
        $cacheDriver = $em->getConfiguration()->getResultCacheImpl();

        if ($cacheDriver) {
            $cacheDriver->flushAll();
        }
    }

    /**
     * function to pull list of faculties and subject codes (aka AANB, ENSC, etc) if available
     *
     * @return array
     */
    public function getFacultySubjectCode()
    {
        $subjectFacultyQuery = $this->getEntityManager()
            ->createQueryBuilder('s')
            ->select(array('s.code', 's.faculty'))
            ->from('UBCExamMainBundle:SubjectFaculty', 's');
        $results = $subjectFacultyQuery->getQuery()
            ->useResultCache(true)
            ->getResult();
        $faculties = array_map(create_function('$o', 'return $o["faculty"];'), $results);  //props to http://stackoverflow.com/questions/1118994/php-extracting-a-property-from-an-array-of-objects
        $subjectCode = array_map(create_function('$o', 'return $o["code"];'), $results);  //props to http://stackoverflow.com/questions/1118994/php-extracting-a-property-from-an-array-of-objects
        $facultiesValue = array_unique(array_values($faculties));
        $subjectCodeValue = array_unique(array_values($subjectCode));
        asort($facultiesValue);
        asort($subjectCodeValue);
        $faculties = array_combine($facultiesValue, $facultiesValue);
        $subjectCode = array_combine($subjectCodeValue, $subjectCodeValue);

        return array($faculties, $subjectCode);
    }

    /**
     * Get all subject code offered by a campus
     *
     * @param $campus string campus, e.g. UBC or UBCO
     * @return array list of the subject code
     */
    public function getSubjectCodeArrayByCampus($campus)
    {
        return $this->getEntityManager()
            ->createQueryBuilder('s')
            ->select(array('s.code', 's.department', 's.faculty', 's.campus'))
            ->from('UBCExamMainBundle:SubjectFaculty', 's')
            ->where('s.campus = :campus')
            ->setParameter('campus', $campus)
            ->getQuery()
            ->useResultCache(true)
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    /**
     * Search subject code by campus and code
     *
     * @param $campus string campus, e.g. UBC or UBCO
     * @param $subjectCode string subject code, e.g. CHIN
     * @return mixed the subject code as an array or null if not exist
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSubjectCodeByCampusAndCode($campus, $subjectCode)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select(array('s.code', 's.department', 's.faculty', 's.campus'))
            ->from('UBCExamMainBundle:SubjectFaculty', 's')
            ->where('s.campus = :campus')
            ->Andwhere('s.code = :code')
            ->setParameter('campus', $campus)
            ->setParameter('code', $subjectCode)
            ->getQuery()
            ->useResultCache(true)
            ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function getFacultiesByCourses($courses)
    {
        $subjects = array_unique(
            array_map(function ($course) {
                    $c = explode(' ', $course);
                    return $c[0];
                }, $courses)
        );

        if (empty($subjects)) {
            return array();
        }

        $faculties = $this->getEntityManager()
            ->createQuery(
                'SELECT DISTINCT s.faculty FROM UBCExamMainBundle:SubjectFaculty s WHERE s.code IN (:subjects)'
            )
            ->setParameter('subjects', $subjects)
            ->useResultCache(true)
            ->getResult();

        return array_map(
            function($faculty) { return $faculty['faculty']; },
            $faculties
        );
    }
}
