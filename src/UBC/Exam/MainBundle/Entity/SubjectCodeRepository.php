<?php

namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SubjectCodeRepository
 */
class SubjectCodeRepository extends EntityRepository
{
    public function refresh(array $codes) {
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
    }

    /**
     * function to pull list of faculties and subject codes (aka AANB, ENSC, etc) if available
     *
     * @param EntityManager $em
     *
     * @return array
     */
    public function getFacultySubjectCode()
    {
        $subjectFacultyQuery = $this->getEntityManager()
            ->createQueryBuilder('s')
            ->select(array('s.code', 's.faculty'))
            ->from('UBCExamMainBundle:SubjectFaculty', 's');
        $results = $subjectFacultyQuery->getQuery()->getResult();
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
}
