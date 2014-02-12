<?php

/**
 * gmail_reader.php
 *
 * this script uses the hm-imap.php library to display unread Gmail messages
 * in a terminal. When started it will display some headers and the
 * text/plain message part for all the unread messages in the INBOX. Then it
 * will sleep for 20 second intervals and poll for new messages, printing
 * a header summary and the text/plain part of any that are found.
 */

/* check for username and password */
if ( isset( $argv[2] ) ) {
    $username = $argv[1];
    $password = $argv[2];
}
else {
    die( "\nyou need to pass your Gmail login and an application password to this script:\n\nphp ./gmail_reader.php jason 123456\n\n" );
}

/* show all notices */
error_reporting ( E_ALL | E_STRICT );

/* set a default timezone */
date_default_timezone_set( 'UTC' );

/* remove unprintable utf-8 chars */
ini_set('mbstring.substitute_character', "none");

/* include IMAP library */
require( 'hm-imap.php' );

/* start it up */
$imap = new Hm_IMAP();

/* connect to gmail's IMAP server in read only mode */
if ( $imap->connect( [
    'username'       => $username,
    'password'       => $password,
    'server'         => 'imap.gmail.com',
    'port'           => 993,
    'tls'            => true,
    'read_only'      => true,
    'use_cache'      => true ] ) ) {

    /* used to track the last new message printed */
    $max_uid = 0;

    /* exchange client/server details */
    $imap->id();

    /* select the INBOX */
    $imap->select_mailbox( 'INBOX' );
    
    /* loop endlessly */
    while ( true ) {

        $imap->poll();

        /* search for unseen messages with the Gmail search API */
        $uids = $imap->google_search( 'in:unread' );

        /* sort by biggest UID */
        rsort( $uids );

        /* will hold as yet unprinted message UIDs */
        $new_uids =  array();

        /* will be the new maximum UID printed */
        $new_max = $max_uid;

        /* loop through results */
        foreach ( $uids as $uid ) {

            /* is the UID greater than the last one printed? */
            if ( $uid > $max_uid ) {

                /* save new message UID */
                $new_uids[] = $uid;

                /* record biggest UID found */
                if ( $uid > $new_max ) {
                    $new_max = $uid;
                }
            }
        }

        /* adjust maximum UID for next check */
        if ( $new_max > $max_uid ) {
            $max_uid = $new_max;
        }

        /* display any new messages found */
        if ( !empty( $new_uids ) ) {

            /* get the headers for the UIDs */
            $headers = $imap->get_message_list( $new_uids );

            /* loop through the list of headers */
            foreach ( $headers as $hdr ) {

                /* get the first text/plain message part */
                $msg = $imap->get_first_message_part( $hdr['uid'], 'text', 'plain' );

                /* make the output ASCII safe and print */
                printf( "\nSubject: \033[1;32m%s\033[0m\n".
                       "Date   : %s\n".
                       "From   : %s\n".
                       "Labels : %s\n".
                       "Size   : %s\n\n".
                       "%s\n\n".
                       "\033[0;37m\t%s\033[0m\n\n".
                       "%s\n\n",
                       mb_convert_encoding( $hdr['subject'], 'ASCII', 'UTF-8' ),
                       mb_convert_encoding( $hdr['date'], 'ASCII', 'UTF-8' ),
                       explode( '<', mb_convert_encoding( $hdr['from'], 'ASCII', 'UTF-8' ) )[0],
                       mb_convert_encoding( $hdr['google_labels'], 'ASCII', 'UTF-8' ),
                       mb_convert_encoding( $hdr['size'], 'ASCII', 'UTF-8' ),
                       str_repeat( '-', 88 ),
                       str_replace( "\n", "\n\t", wordwrap(mb_convert_encoding( $msg, 'ASCII', 'UTF-8' ), 80, "\n", true ) ),
                       str_repeat( '-', 88 )
                );
            }
        }

        /* useful for debugging problems */
        //$imap->show_debug();

        /* wait 20 seconds before checking again */
        sleep( 20 );
    }
}
?>
