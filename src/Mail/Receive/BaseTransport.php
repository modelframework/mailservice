<?php

namespace Mail\Receive;

use ModelFramework\GatewayService\GatewayServiceAwareInterface;
use ModelFramework\GatewayService\GatewayServiceAwareTrait;
use ModelFramework\ModelService\ModelServiceAwareInterface;
use ModelFramework\ModelService\ModelServiceAwareTrait;
use Zend\Mail\Storage\Message;
use Mail\Compose\MailConvert;
use ModelFramework\GatewayService\GatewayInterface;

/**
 * Description of WepoMail
 *
 * @author KSV
 */
abstract class BaseTransport implements GatewayServiceAwareInterface,
                                        ModelServiceAwareInterface
{

    use GatewayServiceAwareTrait, ModelServiceAwareTrait;

    protected $rootFolder = null;


    protected $storeModel = 'MailRaw';
    /**
     * @var GatewayInterface
     */
    protected $storage = null;
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
    protected $setting = null;

    public function __construct( Array $setting, MailConvert $convertor )
    {
        $this->setting   = $setting;
        $this->convertor = $convertor;
    }

    public function lastSyncIsSuccessful()
    {
        return $this->lastSyncSuccessful;
    }

    //fetch all Mails
    protected function openTransport()
    {
        $this->storage =
            $this->getGatewayServiceVerify()->get( $this->storeModel );
        try {
            $protocolName =
                '\\Zend\\Mail\\Storage\\' . $this->setting[ 'protocol_name' ];
//            prn($protocolName);
//            prn($this -> setting);
//            exit;

            $this->transport =
                new $protocolName( $this->setting[ 'protocol_settings' ] );
        } catch ( \Exception $ex ) {
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

    public function convertAll( $uids )
    {
        $settingID = $this->setting[ 'id' ];
        $mailArray = [ ];
        foreach ($uids as $uid) {
            $mail    = $this->storage->find( [
                'protocol_ids.' . $settingID => $this->rootFolder . $uid
            ] )->current();
            $rawMail = new Message( [
                'headers' => $mail->getHeaders()->toString(),
                'content' => $mail->getContent()
            ] );
            $newMail = [ ];
            try {
                $newMail                   =
                    $this->convertor->convertMailToInternalFormat( $rawMail );
                $newMail[ 'protocol_ids' ] =
                    [ $this->setting[ 'id' ] => $this->rootFolder . $uid ];
                $header_id                 =
                    $newMail[ 'header' ][ 'message-id' ];
                $mailArray[ $header_id ]   = $newMail;
                $newMail[ 'error' ]        = $mail->error;
            } catch ( \Exception $ex ) {
                $newMail[ 'error' ][ 'convert' ] = $ex->getMessage();
            }
        }
        return $mailArray;
    }

    /**
     * @param array $exceptProtocolUids
     *
     * @return array
     * @throws \Exception
     */
    public function fetchAll( $exceptProtocolUids = [ ] )
    {
        $this->findRootFolder();

        $exceptProtocolUids = $this->prepareExceptUids( $exceptProtocolUids );

        $uids                     = $this->transport->getUniqueId();
        $uids                     = array_diff( $uids, $exceptProtocolUids );
        $this->lastSyncSuccessful = true;
        $settingID                = $this->setting[ 'id' ];

        //        $uids = ['3AB4A466-FC5E-11E3-89A8-00215AD99F24'];

        $resUids = [];
        foreach ($uids as $uid) {
            //            prn( 'outside' );
            $storeMail = $this->storage->find( [
                'protocol_ids.' . $settingID => $this->getFullUid( $uid )
            ] )->current();
            if (!isset( $storeMail )) {
                $storeMail =
                    $this->getModelServiceVerify()->get( $this->storeModel );
                //                prn( 'inside', $storeMail );
                $storeMail->protocol_ids =
                    [ $settingID => $this->getFullUid( $uid ) ];
                //                prn( $storeMail );
                try {
                    $rawMail =
                        $this->transport->getMessage( $this->transport->getNumberByUniqueId( $uid ) );
                } catch ( \Exception $ex ) {
                    $storeMail->error         =
                        [ 'sync_error' => $ex->getMessage() ];
                    $this->lastSyncSuccessful = false;
                }

                $sameMail =
                    $this->storage->find( [ 'message_id' => $rawMail->message_id ] )
                                  ->current();
                if (isset( $sameMail )) {
                    $storeMail                 = $sameMail;
                    $protocolIds               = $storeMail->protocol_ids;
                    $protocolIds[ $settingID ] =
                        $this->getFullUid( $uid );
                    $storeMail->protocol_ids   = $protocolIds;
                }
            }
            if(!$storeMail->is_converted)
            {
                try {
                    $convertedMail =
                        $this->convertor->convertMailToInternalFormat( $rawMail );
                    $storeMail->is_converted = true;
                } catch ( \Exception $ex ) {
                    $error                    = $storeMail->error;
                    $error[ 'convert_error' ] = $ex->getMessage();
                    $storeMail->error         = $error;
                }
                $storeMail->converted_mail = $convertedMail;
                $storeMail->message_id     = $rawMail->message_id;
                $storeMail->raw_content    = $rawMail->getContent();
                $storeMail->raw_headers    =
                    $rawMail->getHeaders()->toString();
            }
            $resUids[] = $this->getFullUid( $uid );

//            prn( $storeMail );
//            exit;
            $this->storage->save( $storeMail );

        }
//        exit;
        return $resUids;
    }

    protected function getFullUid( $uid )
    {
        return $this->rootFolder . $uid;
    }

    protected function prepareExceptUids( $exceptProtocolUids )
    {
        return $exceptProtocolUids;
    }

    protected function findRootFolder()
    {
        $this->rootFolder = '';
    }

    //return null if protocol doesn't support work with folders, in other way it returns \RecursiveIteratorIterator
    abstract protected function fetchFolders();

//    //case no folder work support in protocol realization
//    public function updateFolders(RecursiveIteratorIterator $directoryStructure)
//    {
//        return true;
//    }
}
