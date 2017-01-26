<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Client;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-12 at 12:29:35.
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Client();
    }

    public function testGetFullName()
    {
        $this->object->setFirstname('first name');
        $this->object->setLastname('last');

        $this->assertEquals('first name&nbsp;last', $this->object->getFullName('&nbsp;'));
    }

    public function testSetterGetters()
    {
        $this->assertEquals('123456', $this->object->setAddress('123456')->getAddress());
        $this->assertEquals('123456@mail.com', $this->object->setEmail('123456@mail.com')->getEmail());
        $this->assertEquals('123456', $this->object->setPhone('123456')->getPhone());
        $this->assertEquals('n4', $this->object->setPostcode('n4')->getPostcode());
    }
}
