<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Mollie_sms {

    function send_sms($recipients, $msg)
    {
    	  require('class.mollie.php');
    	
    	  $sms = new mollie();
    	
    	  // Choose SMS gateway
    	  $sms->setGateway(2);
    	  // Set Mollie.nl username and password
    	  $sms->setLogin('inlognaam', 'wachtwoord');
    	  // Set originator, string of nummer
    	  $sms->setOriginator('Afzender');
    	  // Add recipient(s)
    	  $sms->addRecipients($recipients);
    	  // Add reference (needed for delivery reports)
    	  // $sms->setReference('1234');
    	
    	  // Send the SMS Message
    	  $sms->sendSMS($msg);
    	
    	  if ($sms->getSuccess()) {
    	    return '<b>SMS message is sent to '.$sms->getSuccessCount().' number(s)!</b>';
    	  }
    	  else {
    	    return '<b>Sending the message has failed!</b><br>
    	        Errorcode: ' . $sms->getResultCode() . '<br>
    	        Errormessage: ' . $sms->getResultMessage();
    	  }
    	
    }
}