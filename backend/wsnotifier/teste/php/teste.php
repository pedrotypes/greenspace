<?php

if ($argc < 3 || $argc > 4) {

    echo "Usage: ${argv[0]} <message> <game_id> [player_id]\n";
    exit(1);

}

$destination = Array('game_id' => $argv[2]);
if ($argc == 4) {
    $destination['player_id'] = $argv[3];
}
$message = $argv[1];


require_once __DIR__.'/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;


define('HOST', "localhost");
define('PORT', 5672);
define('USER', "guest");
define('PASS', "guest");
define('VHOST', "/");
$queue = "greenspace.relay_message";

$connection = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$ch         = $connection->channel();


/**
 * name:        $queue
 * passive:     False
 * durable:     True  // the queue will survive server restarts
 * exclusive:   False // the queue can be accessed in other channels
 * auto_delete: False // the queue won't be deleted once the channel is closed.
 */
$ch->queue_declare($queue, FALSE, TRUE, FALSE, FALSE);

$msg = new AMQPMessage(json_encode(Array(
    'destination' => $destination,
    'message' => Array(
        'command' => 'print',
        'timestamp' => time(),
        'message' => $message
    )
)));

/**
 * message:     $msg
 * exchange:    ""
 * routing key: $queue
 */
$ch->basic_publish($msg, '', $queue);

$ch->close();
$connection->close();
