<?php

/**
 * qresync_test.php
 *
 * In order to test this extension 2 connections to the IMAP server are
 * required. The first builds some cache data about a mailbox, the second
 * changes the mailbox state, then the original connection detects and
 * adjusts the cache to that state change.
 */

/* check for username and password */
if ( isset( $argv[2] ) ) {
    $username = $argv[1];
    $password = $argv[2];
}
else {
    die( "\nyou need to pass your IMAP login and password to this script:\n\nphp ./qresync_test.php jason 123456\n\n" );
}

/* show all notices */
error_reporting ( E_ALL | E_STRICT );

/* set a default timezone */
date_default_timezone_set( 'UTC' );

/* include IMAP library */
require( 'hm-imap.php' );

/* start up primary connection */
$imap = new Hm_IMAP();
$imap2 = new Hm_IMAP();

/* connect to gmail's IMAP server in read only mode */
if ( $imap->connect( [
    'username'       => $username,
    'password'       => $password,
    'server'         => '127.0.0.1',
    'use_cache'      => true ] ) ) {

    /* select the INBOX and fetch some headers */
    $folder_detail = $imap->select_mailbox( 'INBOX' );
    $imap->message_action('UNFLAG', array( 18 ) );
    $imap->get_message_list( array( 18 ) );

    /* connect as a second client and change a flag */
    $imap2->connect( [
        'username'       => $username,
        'password'       => $password,
        'server'         => '127.0.0.1',
        'use_cache'      => true
    ] );
    $imap2->select_mailbox( 'INBOX' );
    $imap2->message_action('FLAG', array( 18 ) );
    $imap2->disconnect();

    /* poll should trigger a QRESYNC untagged response */
    $imap->poll();

    $result = $imap->show_debug( false, true );
    if ( !strstr( $result, 'Cache bust avoided' ) ) {
        die( "QRESYNC test FAILED\n" );
    }
    else {
        printf("QRESYNC test PASSED\n");
    }
    $imap->show_debug();
    $imap2->show_debug();
}
?>
