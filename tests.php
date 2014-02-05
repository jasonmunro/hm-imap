<?php

/**
 * Simple tests for the Hm_IMAP class. requires an IMAP account
 * with known content for all the tests to run properly.
 *
 * php ./tests.php imap-username imap-password
 *
 * You will need to check the config in the $imap->connect section
 * and tweak the server/port/etc/ for your particular setup.
 */

/* check for username and password */
if ( isset( $argv[2] ) ) {
    $username = $argv[1];
    $password = $argv[2];
}
else {
    die( "\nyou need to pass your IMAP username and password to this script:\n".
         "\nphp ./tests.php jason 123456\n\n" );
}

/* include the lib */
require('hm-imap.php');

/* default server properties */
$server = '127.0.0.1';
$port = 143;
$passed = 0;

/* show all errors and set a default tz */
error_reporting ( E_ALL | E_STRICT );
date_default_timezone_set( 'UTC' );

/* RUN TESTS */
$imap = new Hm_IMAP();
assert_equal( true, is_object( $imap ) );

$connect = $imap->connect([
    'username' => $username,
    'password' => $password,
    'server' => $server,
    'port' => $port
]);
assert_equal( true, $connect );
assert_equal( 'authenticated', $imap->get_state() );

$caps = $imap->get_capability();
assert_equal( true, strstr( $caps, 'CAPABILITY' ) );

$mailbox_list = $imap->get_mailbox_list();
assert_equal( true, isset($mailbox_list['INBOX']));

$folder_detail = $imap->select_mailbox( 'INBOX' );
assert_equal( 1, $folder_detail['selected'] );
assert_equal( 'selected', $imap->get_state() );

$unseen_uids = $imap->search('UNSEEN');
assert_equal( true, is_array( $unseen_uids) );
assert_equal( true, !empty( $unseen_uids ) );
assert_equal( true, ctype_digit( $unseen_uids[0] ) );

$search_res = $imap->search( 'ALL', '1:100', 'To', 'root' );
assert_equal( true, is_array( $search_res ) );
assert_equal( true, !empty( $search_res ) );
assert_equal( true, ctype_digit( $search_res[0] ) );

$msg_list = $imap->get_message_list( array( 3 ) );
assert_equal( true, isset( $msg_list[3] ) );
assert_equal( 1, count( $msg_list ) );

$sorted_uids = $imap->get_message_sort_order( 'ARRIVAL' );
assert_equal( true, is_array( $sorted_uids ) );
assert_equal( true, !empty( $sorted_uids ) );
assert_equal( true, ctype_digit( $sorted_uids[0] ) );

$struct = $imap->get_message_structure( 3 );
assert_equal( true, is_array( $struct ) );
assert_equal( true, !empty( $struct ) );
assert_equal( 'text', $struct[1]['type'] );

$flat = $imap->flatten_bodystructure( $struct );
assert_equal( array( 1 => 'text/plain'), $flat );

$struct_part = $imap->search_bodystructure( $struct, ['type' => 'text', 'subtype' => 'plain']);
assert_equal( $struct, $struct_part );

$headers = $imap->get_message_headers( 3, 1 );
assert_equal( true, is_array( $headers ) );
assert_equal( true, !empty( $headers ) );
assert_equal( 'root@macbook', $headers['To'] ); 

$size = $imap->start_message_stream( 3, 1 );
assert_equal( 898, $size ); 

while ( $text = $imap->read_stream_line() ) {
    assert_equal( true, strlen( $text ) > 0 );
}
assert_equal( false, $text );

$page = $imap->get_mailbox_page('INBOX', 'ARRIVAL', true, 'ALL', 0, 5);
assert_equal( true, is_array( $page ) );
assert_equal( 5, count( $page ) );

$msg = $imap->get_message_content( 3, 1 );
assert_equal( true, strlen( $msg ) > 0 );

$txt_msg = $imap->get_first_message_part( 3, 'text', 'plain' );
assert_equal( true, strlen( $txt_msg ) );

$fld = $imap->decode_fld( 'test' );
assert_equal( 'test', $fld );

$fld = $imap->decode_fld( '=?UTF-8?B?amFzb24=?=' );
assert_equal( 'jason', $fld );

$sorted_uids = $imap->sort_by_fetch( 'ARRIVAL', true, 'UNSEEN' );
assert_equal( true, is_array( $sorted_uids ) );
assert_equal( true, !empty( $sorted_uids ) );
assert_equal( 207, $sorted_uids[0] );

$nspaces = $imap->get_namespaces();
assert_equal( true, is_array( $nspaces ) );
assert_equal( true, !empty( $nspaces ) );
assert_equal( '/', $nspaces[0]['delim'] );

$created = $imap->create_mailbox( 'test123456789' );
assert_equal( true, $created );

$renamed = $imap->rename_mailbox( 'test123456789', 'test987654321' );
assert_equal( true, $renamed );

$deleted = $imap->delete_mailbox( 'test987654321' );
assert_equal( true, $deleted );

$flagged = $imap->message_action('FLAG', array( 3 ) );
assert_equal( true, $flagged );

$headers = $imap->get_message_headers( 3, 1 );
assert_equal( true, stristr($headers['Flags'], 'flagged' ) );

$unflagged = $imap->message_action('UNFLAG', array( 3 ) );
assert_equal( true, $unflagged );

$imap->disconnect();
assert_equal( 'disconnected', $imap->get_state() );

$imap->show_debug( false );
printf( "\nTests passed: %d\n\n", $passed );

$cache = $imap->dump_cache();
$imap->bust_cache( 'ALL' );
$imap->load_cache( $cache );
assert_equal( true, strlen($cache) > 0 );



/* helper function for test result checking */
function assert_equal( $expected, $actual ) {
    global $passed;
    if ( $actual != $expected ) {
        debug_print_backtrace();
        die(sprintf("assert_equal failed\nexpected: %s\nactual: %s\n",
            $expected, $actual));
    }
    else {
        $passed++;
    }
}
?>
