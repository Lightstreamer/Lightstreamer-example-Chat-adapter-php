<?php
require 'autoload.php';
use Lightstreamer\remote\metadata\LiteralBasedProvider;
use Lightstreamer\remote\MetaDataProviderServer;
use Lightstreamer\remote\DataProviderServer;
use Lightstreamer\remote\IDataProvider;
use Lightstreamer\remote\ItemEventListener;
use Lightstreamer\remote\Server;

class ChatMetadataAdapter extends LiteralBasedProvider
{

    private $data_adaptar;

    function __construct(ChataDataAdapter $data_adapter)
    {
        $this->data_adapter = $data_adapter;
    }

    public function notifySessionClose($session_id)
    {
        echo "Closing session $session_id\n";
        unset($this[$session_id]);
        echo "Session closed\n";
    }

    public function notifyNewSession($user, $session_id, $session_info)
    {
        echo "Notify new session\n";
        $this[$session_id] = $session_info;
        echo "New session <$session_id> for user <$user>\n";
        echo "USER_AGENT <{$session_info['USER_AGENT']}>\n";
        echo "REMOTE_IP <{$session_info['REMOTE_IP']}>\n";
        echo "Created new session $session_id";
    }

    public function notifyUserMessage($user, $session_id, $message)
    {
        $message = explode("|", $message)[1];
        $session_info = $this[$session_id];
        $ip = $session_info["REMOTE_IP"];
        $ua = $session_info["USER_AGENT"];
        $this->data_adapter->sendMessage($ip, $ua, $message);
    }
}

class ChataDataAdapter extends \Stackable implements IDataProvider
{

    private $subscribed;

    private $control;

    private $listener;

    public function init($params)
    {}

    public function subscribe($item)
    {
        $this->subscribed = $item;
    }

    public function unsubscribe($item)
    {
        $this->subscribed = NULL;
    }

    public function isSnapshotAvailable($item)
    {
        return false;
    }

    public function setListener(ItemEventListener $listener)
    {
        $this->listener = $listener;
    }

    public function sendMessage($ip, $nick, $message)
    {
        $update = array(
            "IP" => $ip,
            "nick" => $nick,
            "message" => $message,
            "raw_timestamp" => strval(round(microtime(true) * 1000))
        );
        $this->listener->update($this->subscribed, $update, FALSE);
    }
}

class StarterServer
{

    private $rrPort;

    private $notifyPort;

    private $server;

    public function __construct($host, $rrPort, $notifyPort = null)
    {
        $this->host = $host;
        $this->rrPort = $rrPort;
        $this->notifyPort = $notifyPort;
    }

    public function start(Server $server)
    {
        $this->server = $server;
        $canStart = true;
        if ($rrSocket = stream_socket_client("tcp://{$this->host}:{$this->rrPort}", $errno, $errstr, 5)) {
            $this->server->setRequestReplyHandle($rrSocket);
            
            if (! is_null($this->notifyPort)) {
                if ($notify = stream_socket_client("tcp://{$this->host}:{$this->notifyPort}", $errno, $errstr, 5)) {
                    $this->server->setNotifyHandle($notify);
                } else {
                    $canStart = false;
                }
            }
        } else {
            $canStart = false;
        }
        
        if ($canStart) {
            $this->server->start();
        } else {
            echo "Connection error= [$errno]:[$errstr]\n";
        }
    }
}

/*
 * Print help on the console
 */
function print_help()
{
    echo <<<'EOT'
Usage: php chat.php --host <host> --metadata_rrport <metadata_rrport> --data_rrport <data_rrport> --data_notifport <data_notifport>

    Where: <host>             is the host name or ip address of LS server
           <metadata_rrport>  is the request/reply tcp port number where the Proxy Metadata Adapter is listening on
           <data_rrport>      is the request/reply tcp port where the Proxy DataAdapter is listening on
           <data_notifiport>  is the notify tcp port where the Proxy DataAdapter is listening on


EOT;
}

/* Mandatory command line arguments */
$longoptions = array(
    "host:",
    "metadata_rrport:",
    "data_rrport:",
    "data_notifport:"
);

$options = getopt("", $longoptions);
if (count($options) != 4) {
    print_help();
    exit("Wrong command arguments");
}

try {
    $host = $options["host"];
    $metadata_rrport = $options["metadata_rrport"];
    $data_rrport = $options["data_rrport"];
    $data_notifport = $options["data_notifport"];
    
    $data_adapter = new ChataDataAdapter();
    
    $metadata_adapter = new ChatMetadataAdapter($data_adapter);
    $metadata_server = new MetaDataProviderServer($metadata_adapter);
    $metadataProvidereServerStarter = new StarterServer($host, $metadata_rrport);
    $metadataProvidereServerStarter->start($metadata_server);
    
    $dataprovider_server = new DataProviderServer($data_adapter);
    $dataproviderServerStarter = new StarterServer($host, $data_rrport, $data_notifport);
    $dataproviderServerStarter->start($dataprovider_server);
    
} catch (Exception $e) {
    echo "Caught exception {$e->getMessage()}\n";
}

?>