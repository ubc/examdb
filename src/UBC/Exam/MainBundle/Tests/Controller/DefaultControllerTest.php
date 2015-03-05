<?php

namespace UBC\Exam\MainBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;

/**
 * tests default controller
 * 
 * @author Loong Chan <loong.chan@ubc.ca>
 * @author Pan Luo <pan.luo@ubc.ca>
 *
 */
class DefaultControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'student',
            'PHP_AUTH_PW'   => 'pass',
        ));

        $this->loadFixtures(array(
            'UBC\Exam\MainBundle\Tests\Fixtures\ExamFixtures',
            'UBC\Exam\MainBundle\Tests\Fixtures\SubjectCodeFixtures',
        ));
    }

    /**
     * Smoke testing all the URLs
     * @dataProvider providerUrls
     * @param string $url the url to test
     */
    public function testPageIsSuccessful($url, $username)
    {
        $client = static::createClient(array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => 'pass',
        ));

        touch($this->getContainer()->getParameter('kernel.logs_dir').'/'.'access.log');
        touch($this->getContainer()->getParameter('kernel.logs_dir').'/'.'upload.log');
        $client->followRedirects();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    /**
     * Smoke testing all the URLs
     * @dataProvider providerPublicUrls
     * @param string $url the url to test
     */
    public function testPageIsSuccessfulWithPublicAccess($url)
    {
        $client = self::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function providerPublicUrls()
    {
        return array(
            array('/exam/'),
            array('/exam/wikicontent/CHIN'),
            array('/exam/subjectcode/UBC'),
            array('/exam/subjectcode/UBC/CHIN'),
            array('/exam/guide'),
        );
    }

    public function providerUrls()
    {
        return array(
            array('/exam/', 'student'),
            array('/exam/list', 'instructor'),
            array('/exam/upload', 'instructor'),
            array('/exam/logout', 'student'),
            array('/exam/log', 'admin'),
            array('/exam/log/download/access', 'admin'),
            array('/exam/log/download/upload', 'admin'),
        );
    }

    public function testGetWikiContent()
    {
        $code = new SubjectFaculty();
        $code->setCampus('UBC');
        $code->setFaculty('ARTS');
        $code->setDepartment('ASIA');

        // mock repository
        $mockSubjectCodeRepository = $this->getMockBuilder('\UBC\Exam\MainBundle\Entity\SubjectCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockSubjectCodeRepository->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue(array($code)));

        // mock the EntityManager to return the mock of the repository
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockSubjectCodeRepository));

        // mock buzz
        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(array('getContent', 'getStatusCode'))
            ->getMock();
        $response->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue('<p>This is a test</p>'));
        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $buzz = $this->getMockBuilder('\Buzz\Browser')
            ->setMethods(array('get'))
            ->disableOriginalConstructor()
            ->getMock();
        $buzz->expects($this->any())
            ->method('get')
            ->with($this->equalTo($this->client->getContainer()->getParameter('wiki_base_url').urlencode('UBC/ARTS/ASIA')))
            ->will($this->returnValue($response));

        $this->client->getContainer()->set('doctrine.orm.default_entity_manager', $entityManager);
        $this->client->getContainer()->set('buzz', $buzz);

        $crawler = $this->client->request('GET', '/exam/wikicontent/CHIN');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("test")')->count()
        );
    }

    public function testGetSubjectCodes()
    {
        $this->client->request('GET', '/exam/subjectcode/UBC');

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $response = $this->client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(array(
            'data' => array(
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
            )
        ), $data);


        $this->client->request('GET', '/exam/subjectcode/UBCO');

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $response = $this->client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(array(
            'data' => array()
        ), $data);
    }

    public function testGetSubjectCode()
    {
        $this->client->request('GET', '/exam/subjectcode/UBC/CHIN');

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $response = $this->client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(array(
            'code' => 'CHIN',
            'department' => 'ASIA',
            'faculty' => 'ARTS',
            'campus' => 'UBC'
        ), $data);

        // test non-existing code
        $this->client->request('GET', '/exam/subjectcode/UBC/NONE');

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $response = $this->client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(array(), $data);
    }


    /**
     * This test whether the upload page has validate form
     */
//     public function testUpload()
//     {
//         $this->logIn();
        
//         $crawler = $this->client->request('GET', '/exam/upload');
        
//         $this->assertTrue($crawler->filter('div.content > form.validate-form')->count() === 1);
//     }
    
    public function tearDown()
    {
    }


}
