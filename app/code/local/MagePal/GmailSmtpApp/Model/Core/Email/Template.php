<?php

/**
 *
 */
class MagePal_GmailSmtpApp_Model_Core_Email_Template extends Mage_Core_Model_Email_Template {

    const MODULE_SETTINGS_PATH = 'system/magepal_gmailsmtpapp';
    const MODULE_SETTINGS_PATH_SET_RETURN_PATH =  'system/magepal_gmailsmtpapp/set_return_path';
    const MODULE_SETTINGS_PATH_SET_REPLY_TO =  'system/magepal_gmailsmtpapp/set_reply_to';

    /**
     * Send mail to recipient
     *
     * @param   string      $email		  E-mail
     * @param   string|null $name         receiver name
     * @param   array       $variables    template variables
     * @return  boolean
     * */
    public function send($email, $name = null, array $variables = array()) {
        if (!$this->isValidForSend()) {
            Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
            return false;
        }

        $emails = array_values((array) $email);
        $names = is_array($name) ? $name : (array) $name;
        $names = array_values($names);
        foreach ($emails as $key => $email) {
            if (!isset($names[$key])) {
                $names[$key] = substr($email, 0, strpos($email, '@'));
            }
        }

        $variables['email'] = reset($emails);
        $variables['name'] = reset($names);

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();

        $setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);
        switch ($setReturnPath) {
            case 1:
                $returnPathEmail = $this->getSenderEmail();
                break;
            case 2:
                $returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
                break;
            default:
                $returnPathEmail = null;
                break;
        }
        
        if ($returnPathEmail !== null && $mail->getReturnPath() === NULL && Mage::getStoreConfig(self::MODULE_SETTINGS_PATH_SET_RETURN_PATH)) {
            $mail->setReturnPath($returnPathEmail);
        }

        if ($mail->getReplyTo() === NULL && Mage::getStoreConfig(self::MODULE_SETTINGS_PATH_SET_REPLY_TO)) {
            $mail->setReplyTo($returnPathEmail);
        }


        foreach ($emails as $key => $email) {
            $mail->addTo($email, '=?utf-8?B?' . base64_encode($names[$key]) . '?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if ($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        $mail->setSubject('=?utf-8?B?' . base64_encode($this->getProcessedTemplateSubject($variables)) . '?=');
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        try {
            $systemStoreConfig = Mage::getStoreConfig(self::MODULE_SETTINGS_PATH);

            $emailSmtpConf = array(
                'auth' => strtolower($systemStoreConfig['auth']),
                'ssl' => strtolower($systemStoreConfig['ssl']),
                'username' => $systemStoreConfig['username'],
                'password' => $systemStoreConfig['password']
            );

            $smtp = 'smtp.gmail.com';

            if ($systemStoreConfig['smtphost']) {
                $smtp = strtolower($systemStoreConfig['smtphost']);
            }

            $transport = new Zend_Mail_Transport_Smtp($smtp, $emailSmtpConf);
            $mail->send($transport);
            $this->_mail = null;
        } catch (Exception $ex) {


            try {
                $mail->send(); 
                $this->_mail = null;
            } catch (Exception $ex) {
                $this->_mail = null;


                Mage::logException($ex);
                return false;
            }
            Mage::logException($ex);
            return false;
        }
        return true;
    }

}