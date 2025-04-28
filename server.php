<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Signalling implements MessageComponentInterface {
    protected $clients = []; // [userId => conn]

    function onOpen(ConnectionInterface $conn){
        // query string ?uid=123
        parse_str($conn->httpRequest->getUri()->getQuery(), $qs);
        $uid = $qs['uid'] ?? null;
        if(!$uid){ $conn->close(); return; }
        $conn->uid = $uid;
        $this->clients[$uid] = $conn;
    }

    function onClose(ConnectionInterface $conn){
        unset($this->clients[$conn->uid]);
    }
    function onError(ConnectionInterface $c, \Exception $e){ $c->close(); }

    function onMessage(ConnectionInterface $from, $msg){
        $data = json_decode($msg, true);
        $to   = $data['to'] ?? null;
        if(isset($this->clients[$to])){
            $this->clients[$to]->send($msg); // repassa
        }
    }
}

$server = Ratchet\Server\IoServer::factory(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(new Signalling())
    ), 8080
);
$server->run();
