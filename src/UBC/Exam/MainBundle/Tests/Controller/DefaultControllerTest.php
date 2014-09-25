<?php

namespace UBC\Exam\MainBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UBC\Exam\MainBundle\Entity\User;

/**
 * tests default controller
 * 
 * @author Loong Chan <loong.chan@ubc.ca>
 *
 */
class DefaultControllerTest extends WebTestCase
{
    private $client = null;
    private $user = null;
    
    public function setUp()
    {
        $this->client = static::createClient();
        
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
     * Tests main page
     */
    public function testIndex()
    {
        $crawler = $this->client->request('GET', '/exam');
        
        $this->assertTrue($crawler->filter('#ubc7-header')->count() === 1);
    }
    
    /**
     * Tests going to page that requires auth
     */
    public function testAuth()
    {
        $crawler = $this->client->request('GET', '/exam/list');
        
        $this->assertFalse($crawler->filter('table.list-uploads')->count() === 1);
        
        $this->logIn();
        
        $crawler = $this->client->request('GET', '/exam/list');
        
        $this->assertTrue($crawler->filter('table.list-uploads')->count() === 1);
    }
    
    public function testUpload()
    {
        $this->logIn();
        
        $crawler = $this->client->request('GET', '/exam/list');
        
        $this->assertFalse($crawler->filter('div.content > form.validate-form')->count() === 1);
    }
    
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
