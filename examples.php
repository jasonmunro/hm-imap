<?php

/**
 * Examples of how to use the Hm_IMAP class.
 * You can run all the examples from the command line with:
 *
 * php ./examples.php imap-username imap-password
 *
 * You will need to check the config in the $imap->connect section
 * and tweak the server/port/etc/ for your particular setup.
 */

define( 'INBOX_LISTING',        true ); /* gets the first 10 messages by arrival time and prints their headers */
define( 'NEW_MESSAGE_CHECK',    true ); /* gets the subjects of the 5 newest messages in each mailbox */
define( 'ALL_COMMANDS_EXAMPLE', true ); /* runs all the IMAP commands in the class */
define( 'CACHE_USE_EXAMPLE',    true ); /* uses the built in caching system */

/* include IMAP library */
require('hm-imap.php');

/* check for username and password */
if ( isset( $argv[2] ) ) {
    $username = $argv[1];
    $password = $argv[2];
}
else {
    die( "\nyou need to pass your IMAP username and password to this script:\n\nphp ./example.php jason 123456\n\n" );
}

/* show all notices */
error_reporting ( E_ALL | E_STRICT );

/* set a default timezone */
date_default_timezone_set( 'UTC' );

/* create new object */
$imap = new Hm_IMAP();

/* connect to the specified server. The username and password must be set,
 * as well as any other settings that differ from the defaults */
if ($imap->connect([
    'username'       => $username,    // IMAP username
    'password'       => $password,    // IMAP password
    'server'         => '127.0.0.1',  // IMAP server name or address
    'port'           => 143,          // IMAP server port
    'tls'            => false,        // Use TLS encryption
    'starttls'       => false,        // Use IMAP STARTTLS
    'auth'           => 'login',      // can be login or cram-md5 (cram is untested)
    'read_only'      => true,         // read only IMAP connection
    'search_charset' => false,        // charset for searching, can be US-ASCII, UTF-8, or false
    'sort_speedup'   => true,         // use non-compliant fast sort order processing
    'use_cache'      => false,        // use a built in cache to reduce IMAP server activity
    'folder_max'     => 500 ])) {     // maximum number of mailboxes to fetch in get_mailbox_list()


    /* display the first 10 message headers in the inbox */
    if ( INBOX_LISTING ) {

        /* select the INBOX */
        $folder_detail = $imap->select_mailbox( 'INBOX' );

        /* check the status of the select */
        if ( $folder_detail['selected'] ) {

            /* get the message UIDs in arrival order */
            $uids = $imap->get_message_uids( 'ARRIVAL', true, 'ALL' );

            /* if the INBOX is not empty continue */
            if ( ! empty( $uids ) ) {

                /* get the list of header values for the first 10 UIDs */
                $msg_headers = $imap->get_message_list( array_slice( $uids, 0, 10 ) );

                /* dump the headers */
                print_r( $msg_headers );
            }
        }
    }

    /* search for new mail in every folder */
    if ( NEW_MESSAGE_CHECK ) {

        /* get a list of all the folders in this account */
        $folders = $imap->get_mailbox_list();

        /* loop through the folder list detail */
        foreach ($folders as $folder_name => $folder_atts) {

            /* see if this folder can be selected */
            if ( ! $folder_atts['noselect'] ) {

                /* select the folder */
                $folder_detail = $imap->select_mailbox($folder_name);

                /* if the select operation worked continue */
                if ($folder_detail['selected']) {

                    /* get all unread IMAP uids from this folder */
                    $uids = $imap->get_message_uids( 'ARRIVAL', true, 'UNSEEN' );

                    /* did we find unread messages? */
                    if ( ! empty( $uids ) ) {

                        /* get a list of headers for the first five uids */
                        $headers = $imap->get_message_list( array_slice( $uids, 0, 5) );

                        /* loop through the headers */
                        foreach ( $headers as $uid => $msg_headers ) {

                            /* print the folder, date, IMAP uid and subject of each message */
                            if ( isset( $msg_headers['subject'] ) && isset( $msg_headers['date'] ) ) {
                                printf( "Folder: %s UID: %d Date: %s Subject: %s\n", $folder_name,
                                    $uid, $msg_headers['date'], $msg_headers['subject'] );
                            }
                        }
                    }
                }
            }
        }
    }

    /* run 'em all! */
    if ( ALL_COMMANDS_EXAMPLE ) {

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
            $imap->search( 'To', '1:100', 'search term' );

            /* get sorted list of message uids */
            $imap->get_message_uids();

            /* set the Flagged flag on a message */
            if ( $imap->message_action( 'FLAG', 3 ) ) {

                /* unflag the message */
                $imap->message_action( 'UNFLAG', 3 );
            }

            /* get a nested structure that represents the MIME format of the message parts */
            $struct = $imap->get_message_structure( 3 );

            /* flatten the nested structure into a simple list of IMAP ids and MIME types */
            $imap->flatten_bodystructure( $struct );

            /* filter out text/plain mime types from the message structure */
            $imap->search_bodystructure( $struct, array('type' => 'text', 'subtype' => 'plain'));

            /* get message headers */
            $imap->get_message_headers( 3, 1 );

            /* start streaming message content */
            $imap->start_message_stream( 3, 1 );

            /* loop that reads in lines of a streamed message */
            while ( $line = $imap->read_stream_line() ) { /* do something with $line */ }

            /* get a message part content, or the entire message in a raw format */
            $imap->get_message_content( 3, 1 );

        }
    }

    /* use the cache related functions */
    if ( CACHE_USE_EXAMPLE ) {

        /* turn the cache on */
        $imap->use_cache = true;

        /* select the INBOX */
        if ( $imap->select_mailbox( 'INBOX' )['selected'] ) {

            /* fetch the UIDs, this result should be cached */
            $imap->get_message_uids();

            /* dump the cache into a local variable */
            $cache = $imap->dump_cache();

            /* clear current cache */
            $imap->bust_cache( 'ALL' );

            /* disconnect from IMAP server */
            $imap->disconnect();

            /* debug is reset when we reconnect, so dump this session now */
            $imap->show_debug(false);

            /* reconnect to the IMAP server */
            $imap->connect(array('username' => $username, 'password' => $password));

            /* load the cache data we dumped */
            $imap->load_cache( $cache );

            /* rerun the fetch command, should be served from cache if the state
             * of the INBOX has not changed since dump_cache() was called.
             * The debug output should contain a line like this:
             *
             *      Cache hit for INBOX with: UID SORT (ARRIVAL) US-ASCII ALL
             */
            if ( $imap->select_mailbox( 'INBOX' )['selected'] ) {
                $imap->get_message_uids();
            }
        }
    }
    /* disconnect from the IMAP server */
    $imap->disconnect();
}

/* dump session information */
$imap->show_debug(false);

?>
