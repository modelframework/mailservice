<?php

namespace Mail\Receive;

use ModelFramework\GatewayService\GatewayServiceAwareInterface;
use ModelFramework\GatewayService\GatewayServiceAwareTrait;
use Zend\Mail\Storage\Message;
use Mail\Compose\MailConvert;

/**
 * Description of WepoMail
 *
 * @author KSV
 */
abstract class BaseTransport implements GatewayServiceAwareInterface
{
    protected $storeModel = 'MailRaw';
    use GatewayServiceAwareTrait;
    /**
     * @var \Mail\Compose\MailConvert
     */
    protected $convertor = null;

    /**
     * @var null|bool
     */
    protected $lastSyncSuccessful = null;

    /**
     * @var \Zend\Mail\Storage\AbstractStorage
     */
    protected $transport = null;

    /**
     * @var array
     */
    protected $setting      = null;

    public function __construct(Array $setting, MailConvert $convertor)
    {
        $this->setting = $setting;
        $this->convertor = $convertor;
    }

    public function lastSyncIsSuccessful()
    {
        return $this->lastSyncSuccessful;
    }

    //fetch all Mails
    protected function openTransport()
    {
        try {
            $protocolName         = '\\Zend\\Mail\\Storage\\'.$this->setting[ 'protocol_name' ];
//            prn($protocolName);
//            prn($this -> setting);
//            exit;

            $this->transport = new $protocolName($this->setting[ 'protocol_settings' ]);
        } catch (\Exception $ex) {
            //            prn('open transport problem');
//            prn($ex->getMessage());
//            exit;
            //create checking exception to output normal view, that describes problem to user
            throw $ex;
//            throw new \Exception( 'wrong mail server sync connection' );
        }
//        prn('connection opened');
//        exit;
    }

    protected function closeTransport()
    {
        $this->transport->close();
    }

    public function fetchAll($exceptProtocolUids = [ ])
    {
        $mailArray = [ ];
        $uids      = $this->transport->getUniqueId();
        $uids      = array_diff($uids, $exceptProtocolUids);
        $this->lastSyncSuccessful = true;
        $storeGW = $this->getGatewayServiceVerify()->get($this->storeModel);
        prn($storeGW);
        exit;

//        $uids = ['3AB4A466-FC5E-11E3-89A8-00215AD99F24'];
        foreach ($uids as $uid) {
            try {
//                prn($uid);
                $rawMail = $this->transport->getMessage($this->transport->getNumberByUniqueId($uid));
//                prn($rawMail->getContent());

                $newMail = $this->convertor->convertMailToInternalFormat($rawMail);
//                prn($newMail);
//                exit;

                $newMail['protocol_ids'] = [ $this->setting[ 'id' ] => $uid ];
                $header_id = $newMail['header' ][ 'message-id' ];
                prn( 'successful got ' . $uid, $header_id );

                $mailArray[ $header_id ] = $newMail;
            } catch ( \Exception $ex ) {
                prn( 'problem got ' . $uid, $ex->getMessage() );
//                exit;
                $this->lastSyncSuccessful = false;
            }
        }
//        exit;
        return $mailArray;
    }

    //return null if protocol doesn't support work with folders, in other way it returns \RecursiveIteratorIterator
    abstract protected function fetchFolders();

//    //case no folder work support in protocol realization
//    public function updateFolders(RecursiveIteratorIterator $directoryStructure)
//    {
//        return true;
//    }
}
