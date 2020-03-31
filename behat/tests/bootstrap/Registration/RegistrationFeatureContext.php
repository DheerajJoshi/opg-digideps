<?php

namespace DigidepsBehat\Registration;

use Behat\Gherkin\Node\TableNode;
use DigidepsBehat\Common\BaseFeatureContext;
use DigidepsBehat\Common\EmailTrait;
use DigidepsBehat\Common\LinksTrait;

class RegistrationFeatureContext extends BaseFeatureContext
{
    use EmailTrait;
    use LinksTrait;

    /**
     * @Given the self registration lookup table is empty
     */
    public function theSelfRegistrationLookupTableIsEmpty()
    {
        $query = "DELETE FROM casrec";
        $command = sprintf('psql %s -c "%s"', self::$dbName, $query);
        exec($command);
    }

    /**
     * @Given an admin user uploads the :file file into the Lay CSV uploader
     */
    public function anAdminUserUploadsTheFileIntoTheLayCsvUploader($file)
    {
        $this->iAmLoggedInToAdminAsWithPassword('admin@publicguardian.gov.uk', 'Abcd1234');
        $this->visitAdminPath("/admin/casrec-upload");
        $this->attachFileToField("admin_upload_file", $file);
        $this->pressButton("admin_upload_upload");
    }

    /**
     * @When these deputies register to deputise the following court orders:
     */
    public function theseDeputiesRegisterToDeputiseTheFollowingCourtOrders(TableNode $table)
    {
        foreach ($table as $courtOrder) {
            $this->visit('/register');
            $this->fillField('self_registration_firstname', 'Brian');
            $this->fillField('self_registration_lastname', $courtOrder['deputySurname']);
            $this->fillField('self_registration_email_first', $courtOrder['deputyEmail']);
            $this->fillField('self_registration_email_second', $courtOrder['deputyEmail']);
            $this->fillField('self_registration_postcode', $courtOrder['deputyPostCode']);
            $this->fillField('self_registration_clientFirstname', 'Billy');
            $this->fillField('self_registration_clientLastname', $courtOrder['clientSurname']);
            $this->fillField('self_registration_caseNumber', $courtOrder['caseNumber']);
            $this->pressButton('self_registration_save');

            $this->openActivationOrPasswordResetPage('', 'activation', $courtOrder['deputyEmail']);
            $this->fillField('set_password_password_first', 'Abcd1234');
            $this->fillField('set_password_password_second', 'Abcd1234');
            $this->checkOption('set_password_showTermsAndConditions');
            $this->pressButton('set_password_save');

            $this->fillField('user_details_address1', '102 Petty France');
            $this->fillField('user_details_address2', 'MOJ');
            $this->fillField('user_details_address3', 'London');
            $this->fillField('user_details_addressCountry', 'GB');
            $this->fillField('user_details_phoneMain', '01789 321234');
            $this->pressButton('user_details_save');

            $this->fillField('client_address', '1 South Parade');
            $this->fillField('client_address2', 'First Floor');
            $this->fillField('client_county', 'Notts');
            $this->fillField('client_postcode', 'NG1 2HT');
            $this->fillField('client_country', 'GB');
            $this->fillField('client_phone', '01789432876');
            $this->fillField('client_courtDate_day', '01');
            $this->fillField('client_courtDate_month', '01');
            $this->fillField('client_courtDate_year', '2016');
            $this->pressButton('client_save');

            $this->fillField('report_startDate_day', '02');
            $this->fillField('report_startDate_month', '03');
            $this->fillField('report_startDate_year', '2016');
            $this->fillField('report_endDate_day', '01');
            $this->fillField('report_endDate_month', '03');
            $this->fillField('report_endDate_year', '2017');
            $this->pressButton('report_save');
        }
    }
}
