<?php


namespace UBC\Exam\MainBundle\Tests\Fixtures;


use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;

class SubjectCodeFixtures extends AbstractFixture{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        $code = new SubjectFaculty();
        $code->setCode('CHIN');
        $code->setCampus('UBC');
        $code->setDepartment('ASIA');
        $code->setFaculty('ARTS');
        $code->setUrn('urn:ubc:subjectCode:CHIN~UBC');
        $code->setTitle('Chinese');
        $manager->persist($code);

        $code = new SubjectFaculty();
        $code->setCode('JAPN');
        $code->setCampus('UBC');
        $code->setDepartment('ASIA');
        $code->setFaculty('ARTS');
        $code->setUrn('urn:ubc:subjectCode:JAPN~UBC');
        $code->setTitle('Japanese');
        $manager->persist($code);

        $manager->flush();
    }
}