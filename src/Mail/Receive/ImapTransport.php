<?php

namespace Mail\Receive;

use \RecursiveIteratorIterator;

/**
 * Description of IMAPMAil
 *
 * Fetching emails via IMAP protocols.
 *
 * @author KSV
 */
class IMAPTransport extends BaseTransport
{
    protected $mainFolder = null;

    //
    protected function fetchFolders()
    {
        throw new Exception( 'not implemented' );
    }

    //fetch mails from the biggest folder
    public function fetchAll( $exceptProtocolUids = [ ], $type = null )
    {
        parent::openTransport();

        $folders      = new \RecursiveIteratorIterator( $this -> transport -> getFolders(), \RecursiveIteratorIterator::SELF_FIRST );
        $biggestCount = 0;
        foreach ( $folders as $folder )
        {
            try
            {
                if ( $folder -> isSelectable() )
                {
                    $this -> transport -> selectFolder( $folder -> getGlobalName() );
                    $newBiggestCount = count( $this -> transport -> getUniqueId() );
                    if ( $biggestCount < $newBiggestCount )
                    {
                        $biggestCount       = $newBiggestCount;
                        $this -> mainFolder = $folder;
                    }
                }
                else
                {
                    continue;
                }
            }
            catch ( \Exception $ex )
            {
                
            }
        }

        if ( isset( $this -> mainFolder ) && ($biggestCount > 0) )
        {
            try
            {
                $this -> transport -> selectFolder( $this -> mainFolder -> getGlobalName() );
            }
            catch ( \Exception $ex )
            {
                
            }
        }
        else
        {
            return array();
        }

        foreach ( $exceptProtocolUids as $key => $pUid )
        {
//            prn($mainFolder == substr( $pUid, 0, strlen( $mainFolder ) ));
            if ( $this -> mainFolder == substr( $pUid, 0, strlen( $this -> mainFolder ) ) )
            {
                $exceptProtocolUids[ $key ] = substr( $pUid, strlen( $this -> mainFolder ) );
            }
            else
            {
                unset( $exceptProtocolUids[ $key ] );
//                $exceptProtocolUids[ $key ] = null;
            }
        }
        $mailArray = parent::fetchAll( $exceptProtocolUids, $type );

        foreach($mailArray as $key=>$mail)
        {
            $mail['protocol_ids'][$this -> setting[ 'id' ]] = $this->mainFolder.$mail['protocol_ids'][$this -> setting[ 'id' ]];
            $mailArray[$key] = $mail;
        }
        parent::closeTransport();

        return $mailArray;
    }

    protected function getSettingUid( $uid )
    {
        return [ $this -> setting -> id() => $this -> mainFolder . $uid ];
    }
}
