<?php
/*
  =======================================================================
   File:        class.mollie.php
   Description: See sendsms.php, sendpremiumsms.php or sendmms.php for
                functionality.
   Created:     16-01-2005
   Author:      Mollie B.V.
   Version:     v 2.3 17-06-2009 (long messages)
   Thanks to:   Laurens van Alphen (www.keenondots.com)

   More information? Go to www.mollie.nl
  ========================================================================
   Possible returns:
  ========================================================================
   10 - succesfully sent
   20 - no 'username' given
   21 - no 'password' given
   22 - no or incorrect 'originator' given
   23 - no 'recipients' given
   24 - no 'message' given
   25 - no juiste 'recipients' given
   26 - no juiste 'originator' given
   27 - no juiste 'message' given
   29 - wrong parameter(s)
   30 - incorrect 'username' or 'password'
   31 - not enough credits
   98 - gateway unreachable
   99 - unknown error
  ========================================================================
   Possible returns (Premium SMS):
  ========================================================================
   40 - invalid shortcode
   41 - invalid mid (MO Message ID)
   42 - mid not found
   43 - invalid tariff
   44 - invalid member (of subscription)
   45 - combination of username and keyword/shortcode not found
   46 - member of subscription reached maximum number of messages
  ========================================================================
*/

  class mollie {
    protected $username          = null;
    protected $password          = null;

    protected $gateway           = 1;
    protected $originator        = null;
    protected $recipients        = array();
    protected $reference         = null;
    protected $type              = 'normal';

    protected $premium_shortcode = null;
    protected $premium_keyword   = null;
    protected $premium_tariff    = null;
    protected $premium_mid       = null;
    protected $premium_member    = null;

    protected $success           = false;
    protected $successcount      = 0;
    protected $resultcode        = null;
    protected $resultmessage     = null;

    public function setGateway($gateway) {
      $this->gateway = $gateway;
    }

    public function setType($type) {
      if (in_array($type, array('normal', 'long'))) {
        $this->type = $type;
      }
    }
  
    public function setLogin($username, $password) {
      $this->username = $username;
      $this->password = $password;
    }

    public function setOriginator($originator) {
      $this->originator = $originator;
    }

    public function addRecipients($recipient) {
      array_push($this->recipients, $recipient);
    }

    public function setReference ($reference) {
      $this->reference = $reference;
    }

    public function setPremium ($shortcode, $keyword, $tariff, $mid = null, $member = false) {
      $this->premium_shortcode = $shortcode;
      $this->premium_keyword   = $keyword;
      $this->premium_tariff    = sprintf("%03s",$tariff);
      $this->premium_mid       = $mid;
      $this->premium_member    = ($member === true) ? 'true' : 'false';
    }

    public function getSuccess() {
      return $this->success;
    }

    public function getSuccessCount() {
      return $this->successcount;
    }

    public function getResultCode() {
      return $this->resultcode;
    }

    public function getResultMessage() {
      return $this->resultmessage;
    }

    public function sendSMS($message) {
      $recipients = implode(',', $this->recipients);

      $result = $this->sendToHost('www.mollie.nl', '/xml/sms/',
                     'gateway=' . urlencode($this->gateway) .
                     '&username=' . urlencode($this->username) .
                     '&password=' . urlencode($this->password) .
                     '&originator=' . urlencode($this->originator) .
                     '&recipients=' . urlencode($recipients) .
                     '&type=' . urlencode($this->type) .
                     '&message=' . urlencode($message) .
                     (($this->reference !== null)         ? '&reference=' . urlencode($this->reference) : '') .
                     (($this->premium_shortcode !== null) ? '&shortcode=' . urlencode($this->premium_shortcode) : '') .
                     (($this->premium_keyword !== null)   ? '&keyword=' . urlencode($this->premium_keyword) : '') .
                     (($this->premium_tariff !== null)    ? '&tariff=' . urlencode($this->premium_tariff) : '') .
                     (($this->premium_mid !== null)       ? '&mid=' . urlencode($this->premium_mid) : '') .
                     (($this->premium_member !== null)    ? '&member=' . urlencode($this->premium_member) : '')
                  );

      $this->recipients = array();

      list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
      $this->XMLtoResult($xml);
    }

    public function sendMMS($subject, $message, $attachment = null) {
      $recipients = implode(',', $this->recipients);

      $result = $this->sendToHost('www.mollie.nl', '/xml/mms/',
                     'gateway=' . urlencode($this->gateway) .
                     '&username=' . urlencode($this->username) .
                     '&password=' . urlencode($this->password) .
                     '&originator=' . urlencode($this->originator) .
                     '&recipients=' . urlencode($recipients) .
                     '&subject=' . urlencode($subject) .
                     '&message=' . urlencode($message) .
                     (($attachment !== null)      ? '&attachment[1]=' . urlencode($attachment) : '') .
                     (($this->reference !== null) ? '&reference=' . urlencode($this->reference) : '')
                  );

      $this->recipients = array();

      list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
      $this->XMLtoResult($xml);
    }

    protected function sendToHost($host,$path,$data) {
      $fp  = @fsockopen($host,80);
      $buf = '';
      if ($fp) {
        @fputs($fp, "POST $path HTTP/1.0\n");
        @fputs($fp, "Host: $host\n");
        @fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
        @fputs($fp, "Content-length: " . strlen($data) . "\n");
        @fputs($fp, "Connection: close\n\n");
        @fputs($fp, $data);
        while (!feof($fp)) {
          $buf .= fgets($fp,128);
        }
        fclose($fp);
      }
      return $buf;
    }

    protected function XMLtoResult ($xml) {
      $data = simplexml_load_string($xml);

      $this->success       = ($data->item->success == 'true');
      $this->successcount  = $data->item->recipients;
      $this->resultcode    = $data->item->resultcode;
      $this->resultmessage = $data->item->resultmessage;
    }
  }
