<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
namespace Diamante\EmailProcessingBundle\Model\Mail;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class SystemSettings
{
    /**
     * @var integer
     */
    private $defaultBranchId;

    /**
     * @var string
     */
    private $serverAddress;

    /**
     * @var string
     */
    private $port;

    /**
     * @var bool
     */
    private $sslEnabled;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var boolean
     */
    private $deleteProcessedMessages;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(
        ConfigManager $configManager,
        SymmetricCrypterInterface $encryptor
    ){
        $this->defaultBranchId = (int)$configManager->get('diamante_desk.default_branch');
        $this->serverAddress   = $configManager->get('diamante_email_processing.mailbox_server_address');
        $this->port            = $configManager->get('diamante_email_processing.mailbox_port');
        $this->sslEnabled      = (bool)$configManager->get('diamante_email_processing.mailbox_ssl');
        $this->username        = $configManager->get('diamante_email_processing.mailbox_username');
        $this->password        = $encryptor->decryptData($configManager->get('diamante_email_processing.mailbox_password'));
        $this->deleteProcessedMessages = $configManager->get('diamante_email_processing.mailbox_delete_processed_messages');
    }

    /**
     * @return int
     */
    public function getDefaultBranchId()
    {
        return $this->defaultBranchId;
    }

    /**
     * @return string
     */
    public function getServerAddress()
    {
        return $this->serverAddress;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return bool
     */
    public function getSslEnabled()
    {
        return $this->sslEnabled;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return boolean
     */
    public function getDeleteProcessedMessages()
    {
        return $this->deleteProcessedMessages;
    }
}
