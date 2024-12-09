<?php

require_once "Board.php";

class Game {
    private $board;
    private $players;

    private $area_enclosed_flag = false;

    public function __construct() {
        $this->board = new Board(100, 50);
        $this->players = array();
    }

    public function getBoard() {
        return $this->board;
    }

    public function getPlayers() {
        return $this->players;
    }

    public function wasAnAreaEnclosedInTheLastRound() {
        return $this->area_enclosed_flag;
    }

    public function initNewPlayer($connection, $id, $name, $color) {
        $start_c = $this->board->getRandomStrartingCoords();
        $player = new Player($connection, $id, $name, $color, $start_c, $this->getRandomDirection());
        $this->players[] = $player;
        $this->board->initPlayerArea($start_c, $player);
        $this->board->setResidingPlayerOnTile($start_c, $player);
        return $player;
    }

    public function gameRound() {
        foreach ($this->players as $player) {
            $player->updateDirection();
            $this->board->updatePlayersOldPosTile($player);
            $player->move();
        }

        $dead_players = array();
        $this->area_enclosed_flag = false;
        foreach ($this->players as $player) {
            if (!($this->board->isCoordsValid($player->getPosition()))) {
                $dead_players[] = $player;
                continue;
            }
            $player_pos_trace_owner = $this->board->getTileTraceOwner($player->getPosition()); // player that has a trace on the tile onto which the current player has moved
            if ($player_pos_trace_owner != null) {
                $dead_players[] = $player_pos_trace_owner;
            }
        }

        foreach ($dead_players as $obj) {
            $key = array_search($obj, $this->players, true);
            if ($key !== false) {
                unset($this->players[$key]);
            }
        }

        foreach ($this->players as $player) {
            $player_pos_residing_player = $this->board->getTilesResidingPlayer($player->getPosition()); // player that resides on the tile onto which the current player has moved
            if ($player_pos_residing_player != null) {
                $dead_players[] = $player;
                $dead_players[] = $player_pos_residing_player;
            } else {
                $this->board->setResidingPlayerOnTile($player->getPosition(), $player);
                if ($this->board->getTileOwner($player->getPosition()) === $player && count($player->getTrace()) > 0) {
                    $this->board->encloseArea($player);
                    $this->area_enclosed_flag = true;
                }
            }
        }

        foreach ($dead_players as $obj) {
            $key = array_search($obj, $this->players, true);
            if ($key !== false) {
                unset($this->players[$key]);
            }
        }

        foreach ($dead_players as $player) {
            $this->board->deletePlayersBelongings($player);
        }

        return $dead_players;
    }

    public function getPlayerByConnection($connection) {
        foreach ($this->players as $player) {
            if ($player->getConnection() === $connection) {
                return $player;
            }
        }
        return null;
    }

    public function getPlayerScores() {
        $data = array();
        foreach ($this->players as $player) {
            $data[] = [
                "id" => $player->getId(),
                "score" => $player->getScore()
            ];
        }
        return $data;
    }

    public function getAllData() {
        $players_data = array();
        foreach ($this->players as $player) {
            $players_data[] = [
                "id" => $player->getId(),
                "name" => $player->getName(),
                "color" => $player->getColor(),
                "score" => $player->getScore()
            ];
        }

        $tiles_data = $this->board->getAllTilesData();
        return [
            "players" => $players_data,
            "tiles" => $tiles_data,
        ];
    }

    private function getRandomDirection() {
        $rand = mt_rand(0, 3);
        $direction = null;
        switch($rand) {
            case 0:
                $direction = new Coords(0, 1);
            case 1:
                $direction = new Coords(1, 0);
            case 2:
                $direction = new Coords(0, -1);
            case 3:
                $direction = new Coords(-1, 0);
        }
        return $direction;
    }
}

?>