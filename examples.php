<?php

require('hm-imap.php');

/* show all notices */
error_reporting ( E_ALL | E_STRICT );

/* set a default timezone */
date_default_timezone_set( 'UTC' );

/* create new object */
$imap = new Hm_IMAP();

/* connect to the specified server. The username and password must be set,
 * as well as any other settings that differ from the defaults */
if ($imap->connect([
    'username'       => 'jason',      // IMAP username
    'password'       => 'password',   // IMAP password
    'server'         => '127.0.0.1',  // IMAP server name or address
    'port'           => 143,          // IMAP server port
    'tls'            => false,        // Use TLS encryption
    'starttls'       => false,        // Use IMAP STARTTLS
    'auth'           => 'login',      // can be login or cram-md5 (cram is untested)
    'read_only'      => true,         // read only IMAP connection
    'search_charset' => false,        // charset for searching, can be US-ASCII, UTF-8, or false
    'sort_speedup'   => false,        // use non-compliant fast sort order processing
    'folder_max'     => 500 ])) {     // maximum number of mailboxes to fetch in get_mailbox_list()

    /* get a list of all mailboxes */
    $mailbox_list = $imap->get_mailbox_list();

    /* get the IMAP server capability string */
    $imap->get_capability();

    /* create a new IMAP mailbox */
    if ( $imap->create_mailbox( 'test123' ) ) {

        /* rename the new mailbox to something else */
        if ( $imap->rename_mailbox( 'test123', 'test456' ) ) {

            /* delete the newly created mailbox */
            $imap->delete_mailbox( 'test456' );
        }
    }

    /* select the INBOX */
    if ( $imap->select_mailbox( 'INBOX' )['selected'] ) {

        /* get the uids of unread messages in the selected mailbox */
        $imap->get_unread_messages();

        /* get the headers and flags for the uid list */
        $imap->get_message_list( '1:10' );

        /* search the first 100 messages in the selected mailbox */
        $imap->search( 'To', '1:100', 'root' );

        /* get sorted list of message uids */
        $imap->get_message_uids();

        /* set the Flagged flag on a message */
        if ( $imap->message_action( 'FLAG', 3 ) ) {

            /* unflag the message */
            $imap->message_action( 'UNFLAG', 3 );
        }

        /* get a nested structure that represents the MIME format of the message parts */
        $imap->get_message_structure( 3 );

        /* get message headers */
        $imap->get_message_headers( 3, 1 );

        /* get a message part content, or the entire message in a raw format */
        $imap->get_message_content( 3, 1 );

        /* start streaming message content */
        $imap->start_message_stream( 3, 1 );

        /* loop that reads in lines of a streamed message */
        while ( $line = $imap->read_stream_line() ) { /* do something with $line */ }
    }

    /* disconnect from the IMAP server */
    $imap->disconnect();
}

/* dump session information */
$imap->debug();

?>
