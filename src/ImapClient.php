<?php
namespace  imapClient;
use yii\base\Component;
use yii\base\InvalidConfigException;

class ImapClient extends Component
{
    public $host;
    public $username;
    public $password;
    public $port;
    public $encryption;
    public $mailbox;
    public $format_attachment;
    public $options = [];

    private $_connection;



    public function init()
    {
        parent::init();

        if (!file_exists(\Yii::$app->params['attachmentsPath']['output'])) {
            mkdir(\Yii::$app->params['attachmentsPath']['output'], 0775, true);
        }
        if (!file_exists(\Yii::$app->params['attachmentsPath']['output'].DIRECTORY_SEPARATOR.'src')) {
            mkdir(\Yii::$app->params['attachmentsPath']['output'].DIRECTORY_SEPARATOR.'src', 0775, true);
        }
        if (!file_exists(\Yii::$app->params['attachmentsPath']['fileMail'])) {
            $data=[];
            file_put_contents(\Yii::$app->params['attachmentsPath']['fileMail'],json_encode($data));
        }

        if (empty($this->host) || empty($this->username) || empty($this->password)) {
            throw new InvalidConfigException('Host, username and password must be set');
        }

        $this->connect();
    }

    protected function connect()
    {
        // Исправленная строка подключения с учетом encryption
        $mailbox = "{{$this->host}:{$this->port}/imap{$this->encryption}}{$this->mailbox}";
        $this->_connection = imap_open(
            $mailbox,
            $this->username,
            $this->password,
        );

        if (!$this->_connection) {
            throw new \RuntimeException('IMAP connection failed: ' . imap_last_error());
        }
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    public function getMessages($criteria = 'ALL')
    {

        $emails = imap_search($this->_connection,$criteria);

        if (!$emails) {
            $error = imap_last_error();
        }
        return $emails ?: [];
    }
    public function getMailboxes()
    {
        if (!$this->_connection) {
            throw new \RuntimeException('IMAP connection is not established');
        }

        $mailboxes = imap_list($this->_connection, "{{$this->host}}", "*");


        if (!$mailboxes) {
            return [];
        }
        return array_map(function($mailbox) {
            return str_replace("{{$this->host}}", '', $mailbox);
        }, $mailboxes);
    }

    public function getInfoEmail($keymail)
    {
       return imap_fetchstructure($this->_connection, $keymail);
    }


    public function getAttachments($keymail,$uid)
    {
        $structure = imap_fetchstructure($this->_connection, $keymail);


       if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $attachments[$i] = array(
                    'is_attachment' => false,
                    'filename' => '',
                    'name' => '',
                    'attachment' => ''
                );

                if ($structure->parts[$i]->ifdparameters) {
                    foreach ($structure->parts[$i]->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['filename'] = $object->value;
                            $attachments[$i]['extension'] = strtolower(pathinfo($object->value, PATHINFO_EXTENSION));
                        }
                    }
                }

                if ($structure->parts[$i]->ifparameters) {
                    foreach ($structure->parts[$i]->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['name'] = $object->value;
                            $attachments[$i]['extension'] = strtolower(pathinfo($object->value, PATHINFO_EXTENSION));
                        }
                    }
                }

                if ($attachments[$i]['is_attachment'] && $attachments[$i]['extension'] === $this->format_attachment) {
                    $attachments[$i]['attachment'] = imap_fetchbody($this->_connection, $keymail, $i+1);
                    if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }
            }


            foreach ($attachments as $attachment) {
                $filename = $attachment['name'] ?: $attachment['filename'];
                if ($attachment['is_attachment']  && $attachment['extension'] === 'pdf') {
                    if (empty($filename)) $filename = 'attachment_' . uniqid();
                    file_put_contents(\Yii::$app->params['attachmentsPath']['output'] . $filename, $attachment['attachment']);
                   // $this->deleteMessage($keymail);
                    echo "save attachment: " . $filename . "\n";
                }else{
                    echo $filename." isn`t pdf attachment\n";
                }
                $this->writeUidDownloaded($uid);
            }
        }
    }

    public function getUid($keymail)
    {
        return imap_uid($this->_connection, $keymail);
    }
    public function deleteMessage($keymail)
    {
        imap_delete($this->_connection, $keymail);
    }


    public function downloadAttachments()
    {
        $mails=$this->getMessages();
         $count=0; $messages=0;
        foreach($mails as $key=>$emailkey)
        {
           $uid=$this->getUid($emailkey);

           if(!in_array($uid,self::getUidsDownloaded()))
           {
               $this->getAttachments($emailkey,$uid);
               $count++;
           }
           $messages++;
        }
        echo "Messages:".$messages."Pdf $count attachments downloaded\n";
    }


    static  function getUidsDownloaded()
    {
        $data = file_get_contents(\Yii::$app->params['attachmentsPath']['fileMail']);
       return json_decode($data, true) ?: [];
    }

    public  function writeUidDownloaded($uid)
    {
        $UIDS=$this->uidsDownloaded;
        $arr[]=$uid;
        $Arrsy=array_unique(array_merge($UIDS, $arr));
        file_put_contents(\Yii::$app->params['attachmentsPath']['fileMail'], json_encode($Arrsy));
    }
    public function __destruct()
    {
        if ($this->_connection) {
            @imap_close($this->_connection);
        }
    }
}