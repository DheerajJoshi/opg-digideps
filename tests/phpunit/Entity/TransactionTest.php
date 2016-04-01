<?php
namespace AppBundle\Entity;

use Mockery as m;
use AppBundle\Entity\Report;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    public function testgetNumberOfValidAmounts()
    {
        $t = new Transaction;
        $this->assertEquals([], $t->setAmounts([null, ''])->getNotNullAmounts());
        $this->assertEquals(['0.0', 0.0, 0.01, 0.001], $t->setAmounts(['0.0', 0.0, 0.01, 0.001, null,''])->getNotNullAmounts());
        
    }
    
    
}
