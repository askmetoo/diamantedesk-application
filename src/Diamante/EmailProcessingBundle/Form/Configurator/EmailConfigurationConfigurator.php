<?php

namespace Diamante\EmailProcessingBundle\Form\Configurator;


use Diamante\EmailProcessingBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailConfigurationConfigurator
{
    const KEY_EMAIL_SETTINGS_PASS = 'mailbox_password';

    /** @var SymmetricCrypterInterface */
    protected $encryptor;

    /**
     * @param SymmetricCrypterInterface $encryptor
     * @param ValidatorInterface $validator
     */
    public function __construct(
        SymmetricCrypterInterface $encryptor
    ){
        $this->encryptor = $encryptor;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function configure(FormBuilderInterface $builder, $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $passwordKey = $this->getConfigKey(self::KEY_EMAIL_SETTINGS_PASS);

            if (!$event->getForm()->has($passwordKey)) {
                return;
            }

            $data = (array) $event->getData();

            if (empty($data[$passwordKey]['value'])) {
                $data[$passwordKey]['value'] = $event->getForm()->get($passwordKey)->getData()['value'];
            } else {
                $data[$passwordKey]['value'] = $this->encryptor->encryptData($data[$passwordKey]['value']);
            }

            $event->setData($data);
        }, 4);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getConfigKey($name)
    {
        return Configuration::getConfigKeyByName($name, ConfigManager::SECTION_VIEW_SEPARATOR);
    }
}