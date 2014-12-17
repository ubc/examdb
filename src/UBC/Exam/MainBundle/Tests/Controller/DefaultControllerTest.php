<?php

namespace UBC\Exam\MainBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;
use UBC\Exam\MainBundle\Entity\User;

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
    private $user = null;
    
    public function setUp()
    {
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'user',
            'PHP_AUTH_PW'   => 'userpass',
        ));
        
        //I think I should be using mock objects, but how to do that with interface checks? (instanceof UserInterfac)
        //I believe that this also causes side effect of entering this user into DB. might need to change config_test.yml
        $user = new User();
        $user->setPuid('123456');
        $user->setFirstname('test');
        $user->setLastname('ing');
        $user->setUsername('tester');
        $this->user = $user;
    }

    /**
     * Smoke testing all the URLs
     * @dataProvider providerUrls
     * @param string $url the url to test
     */
    public function testPageIsSuccessful($url)
    {
        $this->client->request('GET', $url);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function providerUrls()
    {
        return array(
            array('/exam/'),
            array('/exam/upload'),
            array('/exam/list'),
            array('/exam/wikicontent/CHIN'),
            array('/exam/subjectcode/UBC'),
            array('/exam/subjectcode/UBC/CHIN'),
        );
    }

    /**
     * Tests main page
     */
    public function testIndex()
    {
        $this->assertTrue(true);    //garbage holder just so it won't throw errors about no tests.
//         $crawler = $this->client->request('GET', '/');
//         
//         $this->assertTrue($crawler->filter('#ubc7-header')->count() === 1);
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
     * Tests going to page that requires auth
     */
//     public function testAuth()
//     {
//         $crawler = $this->client->request('GET', '/exam/list');
        
//         $this->assertFalse($crawler->filter('table.list-uploads')->count() === 1);
        
//         $this->logIn();
        
//         $crawler = $this->client->request('GET', '/exam/list');
        
//         $this->assertTrue($crawler->filter('table.list-uploads')->count() === 1);
//     }

    /**
     * This test whether the upload page has validate form
     */
//     public function testUpload()
//     {
//         $this->logIn();
        
//         $crawler = $this->client->request('GET', '/exam/upload');
        
//         $this->assertTrue($crawler->filter('div.content > form.validate-form')->count() === 1);
//     }
    
    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');
        
        $firewall = 'secured_area';
        $token = new UsernamePasswordToken($this->user, null, $firewall, array('ROLE_USER'));
        $token->setAttributes(array());
        
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();
    
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
    
    public function tearDown()
    {
        unset($this->user);
    }


}
