<?php

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Service\Mailer\MailSenderMock;

class ReportControllerTest extends AbstractTestController
{

    private static $deputy1;
    private static $client1;
    private static $report1;
    private static $deputy2;
    private static $client2;
    private static $report2;
    private static $tokenAdmin = null;
    private static $tokenDeputy = null;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$deputy1 = self::fixtures()->getRepo('User')->findOneByEmail('deputy@example.org');

        self::$client1 = self::fixtures()->createClient(self::$deputy1, ['setFirstname' => 'c1']);
        self::fixtures()->flush();

        self::$report1 = self::fixtures()->createReport(self::$client1);

        // deputy 2
        self::$deputy2 = self::fixtures()->createUser();
        self::$client2 = self::fixtures()->createClient(self::$deputy2);
        self::$report2 = self::fixtures()->createReport(self::$client2);

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


    public function testAddAuth()
    {
        $url = '/report';
        $this->assertEndpointNeedsAuth('POST', $url);

        $this->assertEndpointNotAllowedFor('POST', $url, self::$tokenAdmin);
    }


    public function testAddAcl()
    {
        $url = '/report';
        $this->assertEndpointNotAllowedFor('POST', $url, self::$tokenDeputy, [
            'client' => ['id' => self::$client2->getId()]
        ]);
    }

    private $fixedData = [
        'court_order_type' => 1,
        'start_date' => '2015-01-01',
        'end_date' => '2015-12-31',
    ];


    public function testAdd()
    {
        $url = '/report';

        $reportId = $this->assertJsonRequest('POST', $url, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
                'data' => ['client' => ['id' => self::$client1->getId()]] + $this->fixedData
            ])['data']['report'];

        self::fixtures()->clear();

        // assert creation
        $report = self::fixtures()->getRepo('Report')->find($reportId);
        /* @var $report \AppBundle\Entity\Report */
        $this->assertEquals(self::$client1->getId(), $report->getClient()->getId());
        $this->assertEquals('2015-01-01', $report->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2015-12-31', $report->getEndDate()->format('Y-m-d'));

        $transactionTypesCount = count(self::fixtures()->getRepo('TransactionType')->findAll());
        $this->assertTrue($transactionTypesCount > 1, 'transaction type not added');

        // assert transactions have been added
        $this->assertCount($transactionTypesCount, $report->getTransactions());

//        $this->assertEquals(null, $report->getTransactions()[0]->getAmount());
    }


    public function testGetByIdAuth()
    {
        $url = '/report/' . self::$report1->getId();
        $this->assertEndpointNeedsAuth('GET', $url);

        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenAdmin);
    }


    public function testGetByIdAcl()
    {
        $url2 = '/report/' . self::$report2->getId();

        $this->assertEndpointNotAllowedFor('GET', $url2, self::$tokenDeputy);
    }


    /**
     * @depends testAdd
     */
    public function testGetById()
    {
        $url = '/report/' . self::$report1->getId();

        // assert get groups=basic
        $data = $this->assertJsonRequest('GET', $url, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('court_order_type', $data);
        $this->assertArrayHasKey('report_seen', $data);
        $this->assertArrayNotHasKey('transactions', $data);
        $this->assertEquals(self::$report1->getId(), $data['id']);
        $this->assertEquals(self::$client1->getId(), $data['client']['id']);
        $this->assertArrayHasKey('start_date', $data);
        $this->assertArrayHasKey('end_date', $data);

        // assert accounts
        $data = $this->assertJsonRequest('GET', $url . '?groups=accounts', [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('accounts', $data);

        // assert decisions
        $data = $this->assertJsonRequest('GET', $url . '?groups=decisions', [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('decisions', $data);

        // assert assets
        $data = $this->assertJsonRequest('GET', $url . '?groups=asset', [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('assets', $data);



       
    }


    public function testSubmitAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/submit';

        $this->assertEndpointNeedsAuth('PUT', $url);
        $this->assertEndpointNotAllowedFor('PUT', $url, self::$tokenAdmin);
    }


    public function testSubmitAcl()
    {
        $url2 = '/report/' . self::$report2->getId() . '/submit';

        $this->assertEndpointNotAllowedFor('PUT', $url2, self::$tokenDeputy);
    }


    public function testSubmitNotAllAgree()
    {
        MailSenderMock::resetessagesSent();
        $this->assertEquals(false, self::$report1->getSubmitted());

        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId . '/submit';

        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'submit_date' => '2015-12-30',
                'reason_not_all_agreed' => 'dont agree reason'
            ]
        ]);

        // assert account created with transactions
        $report = self::fixtures()->clear()->getRepo('Report')->find($reportId);
        /* @var $report \AppBundle\Entity\Report */
        $this->assertEquals(true, $report->getSubmitted());
        $this->assertEquals(false, $report->isAllAgreed());
        $this->assertEquals('dont agree reason', $report->getReasonNotAllAgreed());
    }


    public function testSubmit()
    {
        MailSenderMock::resetessagesSent();
        $this->assertEquals(false, self::$report1->getSubmitted());

        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId . '/submit';

        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'submit_date' => '2015-12-30'
            ]
        ]);

        // assert account created with transactions
        $report = self::fixtures()->clear()->getRepo('Report')->find($reportId);
        /* @var $report \AppBundle\Entity\Report */
        $this->assertEquals(true, $report->getSubmitted());
        $this->assertEquals(true, $report->isAllAgreed());

        // todo put back in test for submit date
    }


    public function testUpdateAuth()
    {
        $url = '/report/' . self::$report1->getId();

        $this->assertEndpointNeedsAuth('PUT', $url);
        $this->assertEndpointNotAllowedFor('PUT', $url, self::$tokenAdmin);
    }


    public function testUpdateAcl()
    {
        $url2 = '/report/' . self::$report2->getId();

        $this->assertEndpointNotAllowedFor('PUT', $url2, self::$tokenDeputy);
    }


    public function testUpdate()
    {
        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId;

        // assert get
        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'start_date' => '2015-01-29',
                'end_date' => '2015-12-29',
                'transactions_in' => [
                    ['id' => 'dividends', 'amounts' => [1200.10, 400], 'more_details' => ''],
                    ['id' => 'salary-or-wages', 'amounts' => [760]],
                ],
                'transactions_out' => [
                    ['id' => 'accommodation-other', 'amounts' => [24.50], 'more_details' => 'extra service charge'],
                ],
                'balance_mismatch_explanation' => 'bme'
            ]
        ]);

        
        // both
        $q = http_build_query(['groups' => ['transactionsIn', 'transactionsOut', 'basic']]);
        //assert both groups (quick)
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertTrue(count($data['transactions_in']) > 25);
        $this->assertTrue(count($data['transactions_out']) > 40);
        $this->assertArrayHasKey('start_date', $data);
        $this->assertArrayHasKey('end_date', $data);
        
        // assert transactions_in
        $t = array_shift(array_filter($data['transactions_in'], function($e){ return $e['id']==='dividends'; }));
        $this->assertEquals('in', $t['type']);
        $this->assertEquals('income-and-earnings', $t['category']);
        $this->assertEquals([1200.10, 400], $t['amounts']);
        $this->assertEquals(null, $t['more_details']);
        $this->assertEquals(null, $t['has_more_details']);
        
        
        // assert transactions_out
        $t = array_shift(array_filter($data['transactions_out'], function($e){ return $e['id']==='accommodation-other'; }));
        $this->assertEquals('out', $t['type']);
        $this->assertEquals('accommodation', $t['category']);
        $this->assertEquals([24.50],  $t['amounts']);
        $this->assertEquals(true, $t['has_more_details']);
        $this->assertEquals('extra service charge', $t['more_details']);
        
        $t = array_shift(array_filter($data['transactions_out'], function($e){ return $e['id']==='anything-else-paid-out'; }));
        $this->assertEquals([],  $t['amounts']);
    }


    public function testFormattedAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/formatted/0';
        $this->assertEndpointNeedsAuth('GET', $url);

        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenAdmin);
    }


    public function testFormattedAcl()
    {
        $url2 = '/report/' . self::$report2->getId() . '/formatted/0';

        $this->assertEndpointNotAllowedFor('GET', $url2, self::$tokenDeputy);
    }


    public function testFormatted()
    {
        $url = '/report/' . self::$report1->getId() . '/formatted/0';


        $this->getClient()->request(
            'GET', $url, [], [], ['HTTP_AuthToken' => self::$tokenDeputy]
        );

        $responseContent = $this->getClient()->getResponse()->getContent();
        $this->assertContains('I confirm I have had regard', $responseContent);
    }

}