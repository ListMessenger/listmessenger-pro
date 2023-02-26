<?php
/*
    ListMessenger - Professional Mailing List Management
    Copyright 2009 Silentweb [http://www.silentweb.ca]. All Rights Reserved.

    Developed By: Matt Simpson <msimpson@listmessenger.com>

    For the most recent version, visit the ListMessenger website:
    [http://www.listmessenger.com]

    License Information is found in licence.html
    $Id: lm_mailer.class.php 523 2011-03-12 03:39:11Z matt.simpson $

    ----

    CREDITS:
    PHPMailer extension class originally written by James Collins.
*/

require_once 'classes/phpmailer/class.phpmailer.php';

class LM_Mailer extends PHPMailer
{
    private $ListMessengerId = '';
    private $IncludeNameInAddr = true;

    public $message_type;

    /**
     * Primary object constructor which loads the required PHPMailer object.
     *
     * @param string $called_from
     *
     * @return LM_Mailer
     */
    public function __construct(&$config = [])
    {
        if (!isset($config[PREF_DEFAULT_CHARSET]) ||
            !isset($config[PREF_WORDWRAP]) ||
            !isset($config[PREF_FRMEMAL_ID]) ||
            !isset($config[PREF_FRMNAME_ID]) ||
            !isset($config[PREF_ERREMAL_ID]) ||
            !isset($config[PREF_RPYEMAL_ID]) ||
            !isset($config[PREF_FRMNAME_ID]) ||
            !isset($config[PREF_PROPATH_ID]) ||
            !isset($config[PREF_MAILER_LE]) ||
            !isset($config[PREF_MAILER_INC_NAME]) ||
            !isset($config[REG_SERIAL])) {
            if (isset($config[PREF_ERROR_LOGGING]) && ($config[PREF_ERROR_LOGGING] == 'yes')) {
                error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tRequired sending engine configuration variables were not set.\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
            }

            throw new Exception('ListMessenger encountered a fatal error and was unable to complete the requested action.', self::STOP_CRITICAL);
        }

        $this->PluginDir = $config[PREF_PROPATH_ID].'includes/classes/phpmailer/';

        $this->Priority = 3;
        $this->CharSet = $config[PREF_DEFAULT_CHARSET];
        $this->Encoding = '8bit';
        $this->WordWrap = $config[PREF_WORDWRAP];

        $this->From = $config[PREF_FRMEMAL_ID];
        $this->FromName = $config[PREF_FRMNAME_ID];

        $this->Sender = $config[PREF_ERREMAL_ID];

        $this->ListMessengerId = $config[REG_SERIAL];
        $this->IncludeNameInAddr = (($config[PREF_MAILER_INC_NAME] == 'yes') ? true : false);

        $this->ClearCustomHeaders();

        /*
         * Set the line endings based on the provided configuration.
         */
        switch ($config[PREF_MAILER_LE]) {
            case 'rn':
                $this->LE = "\r\n";
                break;
            case 'r':
                $this->LE = "\r";
                break;
            case 'n':
            default:
                $this->LE = "\n";
                break;
        }

        switch ($config[PREF_MAILER_BY_ID]) {
            case 'mail':
                $this->IsMail();
                break;
            case 'mailadvanced':
                $this->IsMailAdvanced();
                break;
            case 'smtp':
                $this->IsSMTP();
                $this->Host = $config[PREF_MAILER_BY_VALUE];
                $this->SMTPAuth = (($config[PREF_MAILER_AUTH_ID] == 'true') ? true : false);
                if ($this->SMTPAuth) {
                    $this->Username = $config[PREF_MAILER_AUTHUSER_ID];
                    $this->Password = $config[PREF_MAILER_AUTHPASS_ID];
                }

                $this->SMTPKeepAlive = (($config[PREF_MAILER_SMTP_KALIVE] == 'yes') ? true : false);
                break;
            case 'sendmail':
                $this->IsSendmail();
                $this->Sendmail = $config[PREF_MAILER_BY_VALUE];
                break;
            default:
                if (isset($config[PREF_ERROR_LOGGING]) && ($config[PREF_ERROR_LOGGING] == 'yes')) {
                    error_log(display_date('r', time())."\t".__FILE__.' [Line: '.__LINE__."]\tEncountered an unrecognized sending method [".$config[PREF_MAILER_BY_ID]."].\n", 3, $config[PREF_PRIVATE_PATH].'logs/error_log.txt');
                }

                throw new Exception('ListMessenger encountered an unrecognized sending method, and was unable to complete the requested action.', self::STOP_CRITICAL);
                break;
        }

        $this->ClearAllRecipients();
    }

    /**
     * Sets Mailer to send message using PHP mail() function.
     *
     * @return void
     */
    public function IsMailAdvanced()
    {
        $this->Mailer = 'mail';
        $this->set5thParameter = true;
    }

    /**
     * Clears all custom headers, then adds the required LM Headers.
     *
     * @return void
     */
    public function ClearCustomHeaders()
    {
        $this->CustomHeader = [
            ['X-Mailer', 'ListMessenger '.VERSION_TYPE.' '.VERSION_INFO],
            ['X-ListMessenger-ID', urlencode($this->ListMessengerId)],
            ['X-Originating-IP', clean_input($_SERVER['REMOTE_ADDR'], ['emailheaders'])],
        ];
    }

    /**
     * Adds a "To" address.
     *
     * @param string $address
     * @param string $name
     *
     * @return bool true on success, false if address already used
     */
    public function AddAddress($address, $name = '')
    {
        if (!$this->IncludeNameInAddr && ((string) $name != '')) {
            $name = '';
        }

        return $this->AddAnAddress('to', $address, $name);
    }

    /**
     * Adds a "Cc" address.
     * Note: this function works with the SMTP mailer on win32, not with the "mail" mailer.
     *
     * @param string $address
     * @param string $name
     *
     * @return bool true on success, false if address already used
     */
    public function AddCC($address, $name = '')
    {
        if (!$this->IncludeNameInAddr && ((string) $name != '')) {
            $name = '';
        }

        return $this->AddAnAddress('cc', $address, $name);
    }

    /**
     * Adds a "Bcc" address.
     * Note: this function works with the SMTP mailer on win32, not with the "mail" mailer.
     *
     * @param string $address
     * @param string $name
     *
     * @return bool true on success, false if address already used
     */
    public function AddBCC($address, $name = '')
    {
        if (!$this->IncludeNameInAddr && ((string) $name != '')) {
            $name = '';
        }

        return $this->AddAnAddress('bcc', $address, $name);
    }

    /**
     * Adds a "Reply-to" address.
     *
     * @param string $address
     * @param string $name
     *
     * @return bool
     */
    public function AddReplyTo($address, $name = '')
    {
        if (!$this->IncludeNameInAddr && ((string) $name != '')) {
            $name = '';
        }

        return $this->AddAnAddress('ReplyTo', $address, $name);
    }

    /**
     * Set the body wrapping.
     *
     * @return void
     */
    public function SetWordWrap()
    {
        if ($this->WordWrap < 1) {
            return;
        }

        $this->Body = $this->WrapText($this->Body, $this->WordWrap);

        if (in_array($this->message_type, ['alt', 'alt_attachments'])) {
            $this->AltBody = $this->WrapText($this->AltBody, $this->WordWrap);
        }
    }
}
