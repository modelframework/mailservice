<?php

namespace Mail;

use Mail\Compose\DefaultComposeStrategy;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use \Mail\Receive;
use \Mail\Send;
use \Mail\Compose\MailConvert;

/**
 * Description of MailService
 *
 * @author KSV
 */
class MailService implements ServiceLocatorAwareInterface
{

    const PURPOSE_SEND = 'Send';
    const PURPOSE_RECEIVE = 'Receive';

    private $serviceLocator = null;


    /**
     * purpose you want to use service: receive or send emails
     * @param MailService::PURPOSE_SEND|MailService::PURPOSE_RECEIVE $purpose
     *
     * name of protocol you want to use
     * @param string $protocolName
     *
     * settings specified for protocol (look zf2 help)
     * @param array $setting
     *
     * id of setting, witch used to get ot send mail. Can be used to prevent fetching same mails more
     * @param null $settingId
     *
     * @return Receive\BaseTransport|Send\BaseTransport
     * @throws \Exception
     */
    public function getGateway( $purpose, $protocolName, Array $setting, $settingId = null )
    {
        $transportName = 'Mail\\'. $purpose. '\\' . $protocolName . 'Transport';
        if(!class_exists($transportName))
        {
            throw new \Exception('Transport for your mail system work does not exist '.$transportName);
        }
        $convertor = new MailConvert( new DefaultComposeStrategy() );

        $settingArray = [ 'protocol_name' => $protocolName, 'protocol_settings' => $setting ];
        $settingArray[ 'id' ] = $settingId;
        return new $transportName( $settingArray, $convertor );
    }

    public function getServiceLocator()
    {
        return $this -> serviceLocator;
    }

    public function setServiceLocator( ServiceLocatorInterface $serviceLocator )
    {
        $this -> serviceLocator = $serviceLocator;
    }

}
