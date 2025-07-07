# Yii2 imapClient

[![Yii2](https://img.shields.io/badge/Yii_Framework-2.0.48-blue.svg?style=flat-square)](https://www.yiiframework.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://php.net/)

## Settings in Yii config/console.php
 ```php
        'imap'=>[
            'class' => 'app\components\imap\ImapClient', // path to class imapclient.php
            'host' => 'test.host',
            'port' => 993,
            'encryption' => '/ssl', // /tls or /ssl
            'mailbox'=> 'INBOX',
            'username' => 'username@host.do',
            'password' => 'password*',
        ],

 ```

## Settings in Yii config/params.php
```php
      'attachmentsPath'=>[
      'output'   => dirname(__DIR__,2).'/attachments/',                 //path to save pdf
      'fileMail'=> dirname(__DIR__,2).'/attachments/src/mails.json'     //path to mail.json, where uids of emails downloaded
    ],

 ```

## Example for downloading attachments:
```php
    $imap=new \imapClient\ImapClient();
    $imap->format_attachment='pdf';
    $imap->downloadAttachments();
 ```


## Example for downloading attachments:
```php
    $imap=new \imapClient\ImapClient();
    $imap->format_attachment='pdf';
    $imap->downloadAttachments();
 ```

## Example for getting emails information:

```php
    $imap=new \imapClient\ImapClient();
    $imap->getMessages()
 ```
