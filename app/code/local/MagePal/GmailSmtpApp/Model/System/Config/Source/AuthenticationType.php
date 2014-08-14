<?php
class MagePal_GmailSmtpApp_Model_System_Config_Source_AuthenticationType
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'tls', 'label' => Mage::helper('core')->__('TLS (Gmail / Google Apps)')),
    );
  }
}