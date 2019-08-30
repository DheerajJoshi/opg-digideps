<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Organisation;
use AppBundle\Factory\OrganisationFactory;
use PHPUnit\Framework\TestCase;

class OrganisationFactoryTest extends TestCase
{
    /** @var OrganisationFactory */
    private $factory;

    /** @var string[] */
    private $sharedDomains;

    protected function setUp()
    {
        $this->sharedDomains = ['foo.com', 'bar.co.uk'];

        $this->factory = new OrganisationFactory($this->sharedDomains);
    }

    /**
     * @test
     * @dataProvider getEmailVariations
     * @param $fullEmail
     * @param $expectedEmailIdentifier
     */
    public function createFromFullEmail_determinesEmailIdentiferFromTheFullGivenEmail($fullEmail, $expectedEmailIdentifier)
    {
        $organisation = $this->factory->createFromFullEmail('Org Name', $fullEmail, true);

        $this->assertInstanceOf(Organisation::class, $organisation);
        $this->assertEquals('Org Name', $organisation->getName());
        $this->assertEquals($expectedEmailIdentifier, $organisation->getEmailIdentifier());
        $this->assertTrue($organisation->isActivated());
    }

    /**
     * @return array
     */
    public function getEmailVariations(): array
    {
        return [
            ['fullEmail' => 'name@foo.com', 'expectedEmailIdentifier' => 'name@foo.com'],
            ['fullEmail' => 'name@bar.co.uk', 'expectedEmailIdentifier' => 'name@bar.co.uk'],
            ['fullEmail' => 'name@private.com', 'expectedEmailIdentifier' => 'private.com'],
            ['fullEmail' => 'private.com', 'expectedEmailIdentifier' => 'private.com']
        ];
    }

    /**
     * @test
     */
    public function createFromEmailIdentifier_createsOrganisationUsingGivenArgAsEmailIdentifier()
    {
        $organisation = $this->factory->createFromEmailIdentifier('Org Name', 'foo.com', false);

        $this->assertInstanceOf(Organisation::class, $organisation);
        $this->assertEquals('Org Name', $organisation->getName());
        $this->assertEquals('foo.com', $organisation->getEmailIdentifier());
        $this->assertFalse($organisation->isActivated());
    }
}
