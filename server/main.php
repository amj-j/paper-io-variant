<?php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;

require_once __DIR__ . '/vendor/autoload.php';
require_once 'classes/Game.php';

$ws_worker = new Worker("websocket://0.0.0.0:2000");
$ws_worker->count = 1; // 1 proces


$game = new Game();
$new_players = array();
$new_player_id = 1;


$ws_worker->onConnect = function($connection) use (&$new_players) {
    $uuid = uniqid();
    $new_players[] = [
        "connection" => $connection,
        "ready" => false,
        "name" => null,
        "color" => null
    ];
    echo "Player connected; uuid: " . $uuid . "\n";
};

$ws_worker->onMessage = function(TcpConnection $connection, $data) use ($game, &$new_players) {
    $data = json_decode($data, true);
    $player = $game->getPlayerByConnection($connection);
    if ($player == null) {
        $player_init = null;
        foreach ($new_players as &$player_init_i) {
            if ($player_init_i['connection'] == $connection) {
                $player_init = &$player_init_i;
            }
        }
        if ($player_init != null && isset($data['name']) && isset($data['color'])) {
            $player_init['ready'] = true;
            $player_init['name'] = $data['name'];
            $player_init['color'] = $data['color'];
        }
    } else {
        if (isset($data['horizontal']) && isset($data['vertical'])) {
            $player->setRequestedDirection(new Coords($data['horizontal'], $data['vertical']));
        }
    }
};

$ws_worker->onClose = function($connection) use ($game) {
    echo "Player disconnected.\n";
    $player = $game->getPlayerByConnection($connection);
    if ($player != null) {
        $player->setConnection(null);
    }
};

$ws_worker->onWorkerStart = function() use ($game, &$new_players, &$new_player_id) {
    Timer::add(0.5, function() use ($game, &$new_players, &$new_player_id) {
        $new_players_data_for_clients = array(); // array of associative arrays representing player data, that is sent to clients
        for ($i = count($new_players) - 1; $i >= 0; $i--) {
            $player_init = &$new_players[$i];
            if ($player_init['ready']) {
                $new_player = $game->initNewPlayer($player_init['connection'], $new_player_id, $player_init['name'], $player_init['color']);
                $new_players_data_for_clients[] = [
                    "id" => $new_player->getId(),
                    "name" => $new_player->getName(),
                    "score" => $new_player->getScore(),
                    "color" => $new_player->getColor()
                ];
                $new_player_id++;
                $player_init['connection']->send(json_encode($game->getAllData()));
                unset($new_players[$i]);
                $new_players = array_values($new_players);

            }
        }

        if (count($game->getPlayers()) > 1) {
            $dead_players = $game->gameRound();
            $updated_tiles = $game->getBoard()->getUpdatedTilesData();
            $player_scores = null;
            if ($game->wasAnAreaEnclosedInTheLastRound()) {
                $player_scores = $game->getPlayerScores();
            }

            $dead_players_ids = array();
            foreach ($dead_players as $dp) {
                $dead_players_ids[] = $dp->getId();
            }
 
            $update_json = json_encode([
                "new_players" => $new_players_data_for_clients,
                "scores" => $player_scores,
                "tiles" => $updated_tiles,
                "dead_players_ids" => $dead_players_ids
            ]);

            foreach ($game->getPlayers() as $player) {
                if ($player->getConnection() != null) {
                    $player->getConnection()->send($update_json);
                }
            }
            foreach($dead_players as &$dead_player) {
                if ($dead_player->getConnection() != null) {
                    $dead_player->getConnection()->send(json_encode([
                        "message" => "Prehrali ste!",
                        "score" => $dead_player->getScore()
                    ]));
                }
            }
        }
    });
};

Worker::runAll();