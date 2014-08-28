<?php

namespace Mail\Receive;

/**
 * Description of POP3Mail
 *
 * Fetching emails via POP3 protocol
 *
 * @author KSV
 */
class POP3Transport extends BaseTransport
{
    public function fetchFolders()
    {
        return null;
    }

    //return all letters (send and inbox)
    public function fetchAll( $exceptProtocolUids = [ ] )
    {
        parent::openTransport();

        $mailArray = parent::fetchAll( $exceptProtocolUids);
        parent::closeTransport();
        return $mailArray;
    }
}
