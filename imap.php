<?php

include (__DIR__ . "/vendor/autoload.php");


$imap=new \imapClient\ImapClient();
$imap->format_attachment='pdf';
$imap->downloadAttachments();