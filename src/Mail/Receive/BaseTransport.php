<?php

namespace Mail\Receive;

use \Zend\Mail\Storage\Message;
use Mail\Compose\MailConvert;

/**
 * Description of WepoMail
 *
 * @author KSV
 */
abstract class BaseTransport
{

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

    public function __construct( Array $setting, MailConvert $convertor )
    {
        $this -> setting = $setting;
        $this -> convertor = $convertor;
    }


    public function lastSyncIsSuccessful()
    {
        return $this->lastSyncSuccessful;
    }

    //fetch all Mails
    protected function openTransport()
    {
        try
        {
            $protocolName         = '\\Zend\\Mail\\Storage\\' . $this -> setting[ 'protocol_name' ];
            $this -> transport = new $protocolName( $this -> setting[ 'protocol_settings' ] );
        }
        catch ( \Exception $ex )
        {
            //create checking exception to output normal view, that describes problem to user
            throw $ex;
//            throw new \Exception( 'wrong mail server sync connection' );
        }
    }

    protected function closeTransport()
    {
        $this -> transport -> close();
    }

    public function fetchAll( $exceptProtocolUids = [ ] )
    {
        $mailArray = [ ];
        $uids      = $this -> transport -> getUniqueId();
        $uids      = array_diff( $uids, $exceptProtocolUids );
        $this->lastSyncSuccessful = true;

        foreach ( $uids as $uid )
        {
            try
            {
                $rawMail = $this -> transport -> getMessage( $this -> transport -> getNumberByUniqueId( $uid ) );

                $newMail = $this->convertor->convertMailToInternalFormat($rawMail);

                $newMail['protocol_ids'] = [ $this -> setting[ 'id' ] => $uid ];
                $header_id = $newMail['header']['message-id'];

                $mailArray[ $header_id ] = $newMail;
            }
            catch(\Exception $ex)
            {
                $this->lastSyncSuccessful = false;
            }
        }
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
