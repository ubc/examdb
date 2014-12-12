<?php

namespace UBC\Exam\MainBundle\Tests\Entity;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;

class SubjectCodeRepositoryTest extends WebTestCase {
    /**
     * Set up repository test
     */
    public function setUp()
    {
        parent::setUp();
        $this->loadFixtures(array('UBC\Exam\MainBundle\Tests\Fixtures\SubjectCodeFixtures'));
    }

    public function testRefresh()
    {
        $code = new SubjectFaculty();
        $code->setCode('BIOL');
        $code->setCampus('UBC');
        $code->setDepartment('SCIE');
        $code->setFaculty('ARTS');
        $code->setUrn('urn:ubc:subjectCode:BIOL~UBC');
        $code->setTitle('biology');

        $this->getRepository()->refresh(array($code));

        $codes = $this->getRepository()->findAll();

        $this->assertEquals(1, count($codes), 'should purge old data and load the new ones');
    }

    public function testGetFacultySubjectCode()
    {
        list($faculty, $subjects) = $this->getRepository()->getFacultySubjectCode();

        $this->assertEquals(array('ARTS' => 'ARTS'), $faculty);
        $this->assertEquals(array('CHIN' => 'CHIN', 'JAPN' => 'JAPN'), $subjects);
    }

    public function testGetSubjectCodeByCampusAndCode()
    {
        $code = $this->getRepository()
            ->getSubjectCodeByCampusAndCode('UBC', 'CHIN')
        ;

        $this->assertEquals(array(
            'code' => 'CHIN',
            'department' => 'ASIA',
            'faculty' => 'ARTS',
            'campus' => 'UBC'
        ), $code);

        // get non-existing code
        $code = $this->getRepository()
            ->getSubjectCodeByCampusAndCode('UBC', 'NONE')
        ;

        $this->assertNull($code);
    }

    public function testGetSubjectCodeArrayByCampus()
    {
        $codes = $this->getRepository()
            ->getSubjectCodeArrayByCampus('UBC');

        $this->assertEquals(array(
            array(
                'code' => 'CHIN',
                'department' => 'ASIA',
                'faculty' => 'ARTS',
                'campus' => 'UBC'
            ),
            array(
                'code' => 'JAPN',
                'department' => 'ASIA',
                'faculty' => 'ARTS',
                'campus' => 'UBC'
            )
        ), $codes);
    }

    public function testGetFacultiesByCourses()
    {
        $faculties = $this->getRepository()
            ->getFacultiesByCourses(array('CHIN 101'));

        $this->assertEquals(array('ARTS'), $faculties);

        $faculties = $this->getRepository()
            ->getFacultiesByCourses(array('CHIN 101', 'JAPN 101'));

        $this->assertEquals(array('ARTS'), $faculties);

        $faculties = $this->getRepository()
            ->getFacultiesByCourses(array('NONE 101'));

        $this->assertEmpty($faculties);

        $faculties = $this->getRepository()
            ->getFacultiesByCourses(array('CHIN101'));

        $this->assertEmpty($faculties);

        $faculties = $this->getRepository()
            ->getFacultiesByCourses(array());

        $this->assertEmpty($faculties);
    }

    public function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('UBCExamMainBundle:SubjectFaculty');
    }
}
 