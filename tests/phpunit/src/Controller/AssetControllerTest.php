<?php

namespace AppBundle\Controller;

class AssetControllerTest extends AbstractTestController
{

    private static $deputy1;
    private static $client1;
    private static $report1;
    private static $asset1;
    private static $deputy2;
    private static $client2;
    private static $report2;
    private static $asset2;
    private static $tokenAdmin = null;
    private static $tokenDeputy = null;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //deputy1
        self::$deputy1 = self::fixtures()->getRepo('User')->findOneByEmail('deputy@example.org');
        self::$client1 = self::fixtures()->createClient(self::$deputy1, ['setFirstname' => 'c1']);
        self::$report1 = self::fixtures()->createReport(self::$client1);
        self::$asset1 = self::fixtures()->createAsset('other', self::$report1, ['setTitle' => 'title1']);

        // deputy 2
        self::$deputy2 = self::fixtures()->createUser();
        self::$client2 = self::fixtures()->createClient(self::$deputy2);
        self::$report2 = self::fixtures()->createReport(self::$client2);
        self::$asset2 = self::fixtures()->createAsset('other', self::$report2);

        self::fixtures()->flush()->clear();
    }


    /**
     * clear fixtures 
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        self::fixtures()->clear();
    }


    public function setUp()
    {
        if (null === self::$tokenAdmin) {
            self::$tokenAdmin = $this->loginAsAdmin();
            self::$tokenDeputy = $this->loginAsDeputy();
        }
    }


    public function testgetAssetsAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/assets';

        $this->assertEndpointNeedsAuth('GET', $url);
        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenAdmin);
    }


    public function testgetAssetsAcl()
    {
        $url2 = '/report/' . self::$report2->getId() . '/assets';

        $this->assertEndpointNotAllowedFor('GET', $url2, self::$tokenDeputy);
    }


    public function testgetAssets()
    {
        $url = '/report/' . self::$report1->getId() . '/assets';

        // assert get
        $data = $this->assertJsonRequest('GET', $url, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];

        $this->assertCount(1, $data);
        $this->assertEquals(self::$asset1->getId(), $data[0]['id']);
        $this->assertEquals(self::$asset1->getTitle(), $data[0]['title']);
    }


    public function testgetOneByIdAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/asset/' . self::$asset1->getId();

        $this->assertEndpointNeedsAuth('GET', $url);
        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenAdmin);
    }


    public function testgetOneByIdAcl()
    {
        $url2 = '/report/' . self::$report1->getId() . '/asset/' . self::$asset2->getId();
        $this->assertEndpointNotAllowedFor('GET', $url2, self::$tokenDeputy);
    }


    public function testgetOneById()
    {
        $url = '/report/' . self::$report1->getId() . '/asset/' . self::$asset1->getId();

        // assert get
        $data = $this->assertJsonRequest('GET', $url, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];

        $this->assertEquals(self::$asset1->getId(), $data['id']);
        $this->assertEquals(self::$asset1->getTitle(), $data['title']);
    }


    public function testPostAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/asset';

        $this->assertEndpointNeedsAuth('POST', $url);
        $this->assertEndpointNotAllowedFor('POST', $url, self::$tokenAdmin);
    }


    public function testPostAcl()
    {
        $url2 = '/report/' . self::$report2->getId() . '/asset';

        $this->assertEndpointNotAllowedFor('POST', $url2, self::$tokenDeputy);
    }


    public function testPostOther()
    {
        $url = '/report/' . self::$report1->getId() . '/asset';

        $return = $this->assertJsonRequest('POST', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'type' => 'other',
                'value' => 123,
                'description' => 'de',
                'valuation_date' => '01/01/2015',
            ]
        ]);
        $this->assertTrue($return['data']['id'] > 0);

        self::fixtures()->clear();

        $asset = self::fixtures()->getRepo('Asset')->find($return['data']['id']); /* @var $asset \AppBundle\Entity\AssetOther */
        $this->assertInstanceOf('AppBundle\Entity\AssetOther', $asset);
        $this->assertEquals(123, $asset->getValue());
        $this->assertEquals('de', $asset->getDescription());
        $this->assertEquals('01/01/2015', $asset->getValuationDate()->format('m/d/Y'));
        $this->assertEquals(self::$report1->getId(), $asset->getReport()->getId());
    }


    public function testPostProperty()
    {
        $url = '/report/' . self::$report1->getId() . '/asset';

        $return = $this->assertJsonRequest('POST', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'type' => 'property',
                'occupants' => 'other',
                'occupants_info' => 'myself',
                'owned' => 'partly',
                'owned_percentage' => '51',
                'is_subject_to_equity_release' => true,
                'has_mortgage' => true,
                'mortgage_outstanding_amount' => 187500,
                'has_charges' => true,
                'is_rented_out' => true,
                'rent_agreement_end_date' => new \DateTime('2015-12-31'),
                'rent_income_month' => 1200,
                'address' => 'london road',
                'address2' => 'gold house',
                'county' => 'London',
                'postcode' => 'SW1 H11',
                'value' => 250000.50
            ]
        ]);
        $this->assertTrue($return['data']['id'] > 0);

        self::fixtures()->clear();

        $asset = self::fixtures()->getRepo('Asset')->find($return['data']['id']); /* @var $asset \AppBundle\Entity\AssetProperty */

        $this->assertInstanceOf('AppBundle\Entity\AssetProperty', $asset);
        $this->assertEquals('other', $asset->getOccupants());
        $this->assertEquals('myself', $asset->getOccupantsInfo());
        $this->assertEquals('partly', $asset->getOwned());
        $this->assertEquals('51', $asset->getOwnedPercentage());
        $this->assertEquals(true, $asset->getIsSubjectToEquityRelease());
        $this->assertEquals(true, $asset->getHasMortgage());
        $this->assertEquals(187500, $asset->getMortgageOutstandingAmount());
        $this->assertEquals(true, $asset->getHasCharges());
        $this->assertEquals(true, $asset->getIsRentedOut());
        $this->assertEquals('12/31/2015', $asset->getRentAgreementEndDate()->format('m/d/Y'));
        $this->assertEquals(1200, $asset->getRentIncomeMonth());
        $this->assertEquals('london road', $asset->getAddress());
        $this->assertEquals('gold house', $asset->getAddress2());
        $this->assertEquals('London', $asset->getCounty());
        $this->assertEquals('SW1 H11', $asset->getPostcode());
        $this->assertEquals(250000.50, $asset->getValue());
    }


    public function testDeleteAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/asset/'.  self::$asset1->getId();

        $this->assertEndpointNeedsAuth('DELETE', $url);
        $this->assertEndpointNotAllowedFor('DELETE', $url, self::$tokenAdmin);
    }


    public function testDeleteAcl()
    {
        $url2 = '/report/' . self::$report1->getId() . '/asset/'.  self::$asset2->getId();
        $url3 = '/report/' . self::$report2->getId() . '/asset/'.  self::$asset2->getId();

        $this->assertEndpointNotAllowedFor('DELETE', $url2, self::$tokenDeputy);
        $this->assertEndpointNotAllowedFor('DELETE', $url3, self::$tokenDeputy);
    }


    /**
     * Run this last to avoid corrupting the data
     * 
     * @depends testgetAssets
     */
    public function testDelete()
    {
        $url = '/report/' . self::$report1->getId() . '/asset/'.  self::$asset1->getId();
        $this->assertJsonRequest('DELETE', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ]);

        $this->assertTrue(null === self::fixtures()->getRepo('Asset')->find(self::$asset1->getId()));
    }

}