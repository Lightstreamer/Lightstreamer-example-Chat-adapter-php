<?php
require 'autoload.php';
use Lightstreamer\adapters\remote\metadata\LiteralBasedProvider;
use Lightstreamer\adapters\remote\MetaDataProviderServer;
use Lightstreamer\adapters\remote\DataProviderServer;
use Lightstreamer\adapters\remote\IDataProvider;
use Lightstreamer\adapters\remote\ItemEventListener;
use Lightstreamer\adapters\remote\Server;
use lightstreamer\adapters\remote\SubscriptionException;

class ChatMetadataAdapter extends LiteralBasedProvider
{

    private $data_adapter;

    function __construct(ChataDataAdapter $data_adapter)
    {
        $this->data_adapter = $data_adapter;
    }

    public function notifySessionClose($session_id)
    {
        /* Discard session infrormation */
        unset($this[$session_id]);
    }

    public function notifyNewSession($user, $session_id, $session_info)
    {
        /* Register the session details on itself, as LiteralBasedProvider extends \Stackable */
        $this[$session_id] = $session_info;
    }

    public function notifyUserMessage($user, $session_id, $message)
    {
        /* Message must be in the form "CHAT|<message>" */
        $messageTokens = explode("|", $message);
        if (count($messageTokens) != 2) {
            throw new NotificationException("Wrong message received");
        }
        
        if ($messageTokens[0] != "CHAT") {
            throw new NotificationException("Wrong message received");
        }
        
        /* Retrieve the session infos associated to the session_id */
        $session_info = $this[$session_id];
        
        /* Extract the IP and the user agent, to identify the originator of the message */
        $ip = $session_info["REMOTE_IP"];
        $ua = $session_info["USER_AGENT"];
        
        /* Send the message to be pushed to the browsers */
        $this->data_adapter->sendMessage($ip, $ua, $messageTokens[1]);
    }
}

class ChataDataAdapter extends \Stackable implements IDataProvider
{

    const ITEM_NAME = "chat_room";

    private $subscribed;

    private $listener;

    public function init($params)
    {}

    public function subscribe($item)
    {
        if ($item == "chat_room") {
            $this->subscribed = $item;
        } else {
            throw new SubscriptionException("No such item");
        }
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
