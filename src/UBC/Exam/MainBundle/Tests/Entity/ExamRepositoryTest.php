<?php


namespace Entity;


use Liip\FunctionalTestBundle\Test\WebTestCase;

class ExamRepositoryTest extends WebTestCase {
    /**
     * Set up repository test
     */
    public function setUp()
    {
        parent::setUp();
        $this->loadFixtures(array('UBC\Exam\MainBundle\Tests\Fixtures\ExamFixtures'));
    }

    public function testGetAvailableSubjectCodes()
    {
        $public = $this->getRepository()
            ->getAvailableSubjectCodes(0, array(), array());

        $this->assertEquals(3, count($public), 'should have 3 public access exams');
        array_walk($public, function($c) {
            $this->assertStringEndsWith('101', $c, 'each public exam course number should end with 101');
        });


        $public_auth = $this->getRepository()
            ->getAvailableSubjectCodes(1, array(), array());
        $auth = array_diff($public_auth, $public);

        $this->assertEquals(2, count($auth), 'should have 2 public and authenticated user exams');
        array_walk($auth, function($c) {
            $this->assertStringEndsWith('201', $c, 'each authenticated user exams course number should end with 201');
        });


        $public_auth_faculty = $this->getRepository()
            ->getAvailableSubjectCodes(1, array('ARTS'), array());
        $faculty = array_diff($public_auth_faculty, $public_auth);

        $this->assertEquals(3, count($faculty), 'should have 3 faculty level exam');
        array_walk($faculty, function($c) {
            $this->assertStringEndsWith('301', $c, 'each faculty level exam course number should end with 301');
        });


        $public_auth_course = $this->getRepository()
            ->getAvailableSubjectCodes(1, array(), array('LFS 200'));
        $course = array_diff($public_auth_course, $public_auth);

        $this->assertEquals(1, count($course), 'should have 1 course level exam');
        $this->assertEquals('LFS 200', $course[0], 'course level exam should have course number LFS 200');


        $code = $this->getRepository()
            ->getAvailableSubjectCodes(0, array('ARTS'), array('LFS 200'));
        $this->assertEquals(3, count($code), 'should only have 3 public exam');
    }

    public function testFindExamsByCourse()
    {
        $exmas = $this->getRepository()
            ->findExamsByCourse('LFS 200', 1, array(), array('LFS 200'));

        $this->assertEquals(1, count($exmas));
        $this->assertAttributeEquals('LFS 200', 'subject_code', $exmas[0]);

        $exmas = $this->getRepository()
            ->findExamsByCourse('CHIN 200', 1, array('ARTS'), array('LFS 200'));

        $this->assertEmpty($exmas);
    }

    public function testFindEditableExamById()
    {
        $exam = $this->getRepository()
            ->findEditableExamById(1, 0);
        $this->assertNull($exam, 'find public exam by id 1 with non-authenticated user');

        $exam = $this->getRepository()
            ->findEditableExamById(4, 0);
        $this->assertNull($exam, 'find authentication required exam by id 4 with non-authenticated user');

        $exam = $this->getRepository()
            ->findEditableExamById(4, 1);
        $this->assertNull($exam, 'find authentication required exam by id 4 with non owner user');

        $exam = $this->getRepository()
            ->findEditableExamById(9, 3);
        $this->assertEquals(9, $exam->getId(), 'find exam by id 9 with the owner credential');

        $exam = $this->getRepository()
            ->findEditableExamById(9, -1);
        $this->assertEquals(9, $exam->getId(), 'find exam by id 9 with the admin credential');
    }

    public function testGetStats()
    {
        $result = ($this->getRepository()
            ->getExamStats());
        foreach($result as $stat) {
            if ($stat['faculty'] == 'LFS') {
                $this->assertEquals('2', $stat['uploads']);
                $this->assertEquals('4', $stat['downloads']);
            }
        }
    }

    public function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('UBCExamMainBundle:Exam');
    }
}