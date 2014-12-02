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
}
