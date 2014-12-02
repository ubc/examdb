<?php


namespace UBC\Exam\MainBundle\Tests\Command;


use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;
use UBC\Exam\MainBundle\Command\SubjectCodeRefreshCommand;
use UBC\SISAPI\Entity\DepartmentCode;
use UBC\SISAPI\Entity\DepartmentCodes;
use UBC\SISAPI\Entity\SubjectCode;
use UBC\SISAPI\Entity\SubjectCodes;

class SubjectCodeRefreshCommandTest extends WebTestCase
{
    public function testExecute()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->add(new SubjectCodeRefreshCommand());

        $command = $application->find('exam:subjectcode:refresh');
        $command->setContainer($this->getMockContainer());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
            )
        );

    }

    private function getMockContainer()
    {
        // mock logger
        $logger = $this->getMockBuilder('Symfony\Bridge\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $logger->expects($this->any())
            ->method('info')
            ->will($this->returnSelf());

        // mock services
        $mockSubjectCodeService = $this->getMockBuilder('UBC\SISAPI\Service\SubjectCodeService')
            ->disableOriginalConstructor()
            ->getMock();
        $mockSubjectCodeService->expects($this->once())
            ->method('getSubjectCodes')
            ->will($this->returnValue($this->getSubjectCodes()));

        $mockDepartmentCodeService = $this->getMockBuilder('UBC\SISAPI\Service\DepartmentCodeService')
            ->disableOriginalConstructor()
            ->getMock();
        $mockDepartmentCodeService->expects($this->once())
            ->method('getDepartmentCodes')
            ->will($this->returnValue($this->getDepartmentCodes()));

        // mock repository
        $mockSubjectCodeRepository = $this->getMockBuilder('\UBC\Exam\MainBundle\Entity\SubjectCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockSubjectCodeRepository->expects($this->once())
            ->method('refresh')
            ->will($this->returnCallback(array($this, 'mockRefresh')));

        // mock the EntityManager to return the mock of the repository
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($mockSubjectCodeRepository));

        // mock the doctrine registry to return the mock entity manager
        $registry = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $mapping = array(
            array('logger', Container::EXCEPTION_ON_INVALID_REFERENCE, $logger),
            array('sisapi.subject_code', Container::EXCEPTION_ON_INVALID_REFERENCE, $mockSubjectCodeService),
            array('sisapi.department_code', Container::EXCEPTION_ON_INVALID_REFERENCE, $mockDepartmentCodeService),
            array('doctrine', Container::EXCEPTION_ON_INVALID_REFERENCE, $registry)
        );

        // mock container
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($mapping));

        return $container;
    }

    public function mockRefresh(array $codes) {
        $this->assertEquals(3, count($codes));
        $counter = 0;
        foreach($codes as $c) {
            if ($c->getUrn() == 'urn:ubc:subjectCode:AWFA~UBC') {
                $this->assertEquals('AWFA', $c->getCode());
                $this->assertEquals('AWARDS + FINANCIAL AID', $c->getTitle());
                $this->assertEquals('UBC', $c->getCampus());
                $this->assertEquals('AWFA', $c->getDepartment());
                $this->assertEquals('', $c->getFaculty());
                $counter++;
            } else if ($c->getUrn() == 'urn:ubc:subjectCode:BKST~UBC') {
                $this->assertEquals('BKST', $c->getCode());
                $this->assertEquals('BOOKSTORE', $c->getTitle());
                $this->assertEquals('UBC', $c->getCampus());
                $this->assertEquals('BKST', $c->getDepartment());
                $this->assertEquals('', $c->getFaculty());
                $counter++;
            } else if ($c->getUrn() == 'urn:ubc:subjectCode:AMS~UBC') {
                $this->assertEquals('AMS', $c->getCode());
                $this->assertEquals('ALMA MATER SOCIETY', $c->getTitle());
                $this->assertEquals('UBC', $c->getCampus());
                $this->assertEquals('AMS', $c->getDepartment());
                $this->assertEquals('AMS', $c->getFaculty());
                $counter++;
            }
        }
        $this->assertEquals(3, $counter);
    }

    public function getSubjectCodes()
    {
        $codes = array();

        $c = new SubjectCode();
        $c->setId('urn:ubc:subjectCode:AWFA~UBC');
        $c->setAdminCampusCode('UBC');
        $c->setFullDescription('AWARDS + FINANCIAL AID');
        $c->setDepartmentCode('AWFA');
        $codes[] = $c;

        $c = new SubjectCode();
        $c->setId('urn:ubc:subjectCode:BKST~UBC');
        $c->setAdminCampusCode('UBC');
        $c->setFullDescription('BOOKSTORE');
        $c->setDepartmentCode('BKST');
        $codes[] = $c;

        $c = new SubjectCode();
        $c->setId('urn:ubc:subjectCode:AMS~UBC');
        $c->setAdminCampusCode('UBC');
        $c->setFullDescription('ALMA MATER SOCIETY');
        $c->setDepartmentCode('AMS');
        $codes[] = $c;

        $r = new SubjectCodes();
        $r->codes = $codes;

        return $r;
    }

    public function getDepartmentCodes()
    {
        $codes = array();

        $c = new DepartmentCode();
        $c->setId('urn:ubc:departmentCode:AWFA~UBC');
        $c->setCode('AWFA');
        $c->setAdminCampusCode('UBC');
        $c->setFullDescription('AWARDS + FINANCIAL AID');
        $c->setAdminFacultyCode('');
        $codes[] = $c;

        $c = new DepartmentCode();
        $c->setId('urn:ubc:departmentCode:BKST~UBC');
        $c->setCode('BKST');
        $c->setAdminCampusCode('UBC');
        $c->setFullDescription('BOOKSTORE');
        $codes[] = $c;

        $c = new DepartmentCode();
        $c->setId('urn:ubc:departmentCode:AMS~UBC');
        $c->setCode('AMS');
        $c->setAdminCampusCode('UBC');
        $c->setFullDescription('ALMA MATER SOCIETY');
        $c->setAdminFacultyCode('AMS');
        $codes[] = $c;

        $r = new DepartmentCodes();
        $r->codes = $codes;

        return $r;
    }
}
 