<?php

namespace UBC\Exam\MainBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use UBC\Exam\MainBundle\Entity\User;


class UserControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp()
    {
        parent::setUp();

        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'pass',
        ));

        $this->loadFixtures(array('UBC\Exam\MainBundle\Tests\Fixtures\ExamFixtures'));
    }

    public function providerUrls()
    {
        return array(
            array('/exam/user'),
            array('/exam/user/new'),
            array('/exam/user/2'),
            array('/exam/user/2/edit'),
        );
    }

    /**
     * Smoke testing all the URLs
     * @dataProvider providerUrls
     * @param string $url the url to test
     */
    public function testPageIsSuccessful($url)
    {
        $this->client->followRedirects();
        $this->client->request('GET', $url);

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testEditSuperAdminUser()
    {
        $this->client->request('GET', '/exam/user/1/edit');

        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );

    }

    public function testEditAdminUser()
    {
        $this->client->request('GET', '/exam/user/2/edit');

        $this->assertEquals(
            200,
            $this->client->getResponse()->getStatusCode()
        );

    }

    public function testCreateSuperAdminUser()
    {
        $crawler = $this->client->request('GET', '/exam/user/');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $crawler = $this->client->click($crawler->selectLink('Create a new User')->link());

        // Fill in the form and submit it
        $form = $crawler->selectButton('Create')->form(array(
            'ubc_exam_mainbundle_user[username]'  => 'Test',
            'ubc_exam_mainbundle_user[firstname]'  => 'Test',
            'ubc_exam_mainbundle_user[lastname]'  => 'Test',
            'ubc_exam_mainbundle_user[roleString]'  => 'ROLE_SUPER_ADMIN',
        ));

        $this->client->submit($form);

        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testCreateUser()
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', '/exam/user/');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $crawler = $this->client->click($crawler->selectLink('Create a new User')->link());

        // Fill in the form and submit it
        $form = $crawler->selectButton('Create')->form(array(
            'ubc_exam_mainbundle_user[username]'  => 'Test',
            'ubc_exam_mainbundle_user[firstname]'  => 'Test',
            'ubc_exam_mainbundle_user[lastname]'  => 'Test',
            'ubc_exam_mainbundle_user[roleString]'  => 'ROLE_USER',
        ));

        $crawler = $this->client->submit($form);

        $this->assertTrue($crawler->filter('td:contains("Test")')->count() > 0);
    }

    public function testEditUser()
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', '/exam/user/3');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $crawler = $this->client->click($crawler->selectLink('Edit')->link());

        // Fill in the form and submit it
        $form = $crawler->selectButton('Edit')->form(array(
            'ubc_exam_mainbundle_user[username]'  => 'TestEdit',
            'ubc_exam_mainbundle_user[firstname]'  => 'TestEdit',
            'ubc_exam_mainbundle_user[lastname]'  => 'TestEdit',
            'ubc_exam_mainbundle_user[roleString]'  => 'ROLE_USER',
        ));

        $crawler = $this->client->submit($form);

        $this->assertTrue($crawler->filter('[value="TestEdit"]')->count() > 0);
    }

    public function testDeleteUser()
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', '/exam/user/3');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->client->submit($crawler->selectButton('Delete')->form());

        $this->assertNotRegExp('/TestEdit/', $this->client->getResponse()->getContent());
    }
}