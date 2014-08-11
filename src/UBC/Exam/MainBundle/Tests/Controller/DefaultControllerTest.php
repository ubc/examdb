<?php

namespace UBC\Exam\MainBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
	//make sure you can visit main page
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/exam');

        $this->assertTrue($crawler->filter('title:contains("University of British Columbia")')->count() === 1);
        $this->assertTrue($crawler->filter('#ubc7-header')->count() === 1);
    }
    
    
}
