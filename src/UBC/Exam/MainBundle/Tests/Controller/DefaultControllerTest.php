<?php

namespace UBC\Exam\MainBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * tests default controller
 * 
 * @author Loong Chan <loong.chan@ubc.ca>
 *
 */
class DefaultControllerTest extends WebTestCase
{
    /**
     * Tests main page
     */
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/exam');

        $this->assertTrue($crawler->filter('title:contains("University of British Columbia")')->count() === 1);
        $this->assertTrue($crawler->filter('#ubc7-header')->count() === 1);
    }
    
    /**
     * Tests listing of courses
     */
    public function testList()
    {
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/exam/list');
        
        $this->assertTrue($crawler->filter('table.list-uploads')->count() === 1);
    }
}
