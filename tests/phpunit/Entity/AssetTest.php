<?php
namespace AppBundle\Entity;

class AssetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Asset
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Asset();
    }

    public function testSetterGetters()
    {
        $this->assertEquals('123456', $this->object->setExplanation('123456')->getExplanation());
        $this->assertEquals('123456', $this->object->setTitle('123456')->getTitle());
        $this->assertEquals('123456', $this->object->setValue('123456')->getValue());
    }

    
}
