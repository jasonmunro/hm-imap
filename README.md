hm-imap
=======

IMAP client protocol implementation using PHP5.

This is an adaptation of the IMAP class used by the Hastymail open source
webmail program. This implementation is intended to provide "drop in" IMAP
client abilities with very little PHP requirements. This library reads and
writes directly to the IMAP server and does not use the PHP IMAP client
functions. This is not a complete representation of RFC 3501, however it does
provide a large subset of the functionality. 

