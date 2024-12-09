<?php

require_once "Coords.php";
require_once "Tile.php";
require_once "Player.php";

class Board {
    private $tiles;
    private $updated_tiles;

    public function __construct($width, $height) {
        $this->tiles = array();
        for ($x = 0; $x < $width; $x++) { 
            $this->tiles[$x] = array_fill(0, $height, null);
        }

        $this->updated_tiles = array();
    }

    public function getUpdatedTiles() {
        return $this->updated_tiles;
    }

    public function getRandomStrartingCoords() {
        while (true) {
            $start_c = new Coords(mt_rand(1, count($this->tiles) - 2), mt_rand(1, count($this->tiles[0]) - 2));
            $is_valid = true;
            foreach ($this->getTileNeighboursCoordsInclDiagonal($start_c) as $neighbour) {
                if ($this->tiles[$neighbour->x][$neighbour->y] != null) {
                    $is_valid = false;
                    break;
                }
            }
            if ($is_valid) {
                return $start_c;
            }
        }
    }

    public function initPlayerArea($start_c, $player) {
        $this->occupyTile($start_c, $player);
        foreach($this->getTileNeighboursCoordsInclDiagonal($start_c) as $neighbour_c) {
            $this->occupyTile($neighbour_c, $player);
        }

    }

    public function encloseArea($player) {
        foreach ($player->getTrace() as $coords) {
            $this->occupyTile($coords, $player);
        }

        $first_inner_t = $this->findFirstInnerTile($player);
        while ($first_inner_t != null) {
            $this->occupyAreaRecursively($first_inner_t, $player);
            $first_inner_t = $this->findFirstInnerTile($player);
        }

        $player->deleteTrace();
    }

    public function deletePlayersBelongings($player) {
        foreach ($player->getTrace() as $coords) {
            $tile = $this->tiles[$coords->x][$coords->y];
            if ($tile == null) {
                continue;
            } else if ($tile->getOwner() == null && $tile->getResidingPlayer() == null) {
                $this->tiles[$coords->x][$coords->y] = null;
            } else {
                $tile->setTraceOwner(null);
            }
            $this->updated_tiles[] = $coords;
        }

        if (count($player->getTrace()) > 0) {
            foreach ($this->getTileNeighboursCoords($player->getTrace()[0]) as $neighbour_coords) {
                if ($this->isTileOccupiedBy($neighbour_coords, $player)) {
                    $player_score = $player->getScore();
                    $this->clearAreaRecursively($neighbour_coords, $player);
                    $player->setScore($player_score);
                    break;
                }
            }
        } else {
            foreach ($this->getTileNeighboursCoords($player->getPosition()) as $neighbour_coords) {
                if ($this->isTileOccupiedBy($neighbour_coords, $player)) {
                    $player_score = $player->getScore();
                    $this->clearAreaRecursively($neighbour_coords, $player);
                    $player->setScore($player_score);
                    break;
                }
            }
        }

        $player_pos = $player->getPosition();
        if ($this->isCoordsValid($player_pos)) {
            $player_residing_tile = $this->tiles[$player_pos->x][$player_pos->y];
            if ($player_residing_tile != null) {
                if ($player_residing_tile->getOwner() == null && $player_residing_tile->getTraceOwner() == null) {
                    $this->tiles[$player_pos->x][$player_pos->y] = null;
                } else {
                    $player_residing_tile->setResidingPlayer(null);
                }
                $this->updated_tiles[] = $player_pos;
            }
        }
    }

    public function isCoordsValid($coords) {
        return 
            $coords->x >= 0 && 
            $coords->y >= 0 &&
            $coords->x < count($this->tiles) &&
            $coords->y < count($this->tiles[0])
        ;
    }

    public function getTileOwner($coords) {
        $tile = $this->tiles[$coords->x][$coords->y];
        if ($tile == null) {
            return null;
        } else {
            return $tile->getOwner();
        }
    }

    public function getTileTraceOwner($coords) {
        $tile = $this->tiles[$coords->x][$coords->y];
        if ($tile == null) {
            return null;
        } else {
            return $tile->getTraceOwner();
        }
    }

    public function getTilesResidingPlayer($coords) {
        $tile = $this->tiles[$coords->x][$coords->y];
        if ($tile == null) {
            return null;
        } else {
            return $tile->getResidingPlayer();
        }
    }

    public function updatePlayersOldPosTile($player) { // update the tile that the player is moving from
        $old_pos = $player->getPosition();
        if (!($this->isTileOccupiedBy($old_pos, $player))) {
            $this->tiles[$old_pos->x][$old_pos->y]->setTraceOwner($player);
            $player->addToTrace($old_pos);
        }
        $this->tiles[$old_pos->x][$old_pos->y]->setResidingPlayer(null);
        $this->updated_tiles[] = clone $old_pos;

    }

    public function setResidingPlayerOnTile($coords, $player) {
        if ($this->tiles[$coords->x][$coords->y] == null) {
            $tile = new Tile();
            $tile->setResidingPlayer($player);
            $this->tiles[$coords->x][$coords->y] = $tile;
        } else {
            $this->tiles[$coords->x][$coords->y]->setResidingPlayer($player);
        }    

        if ($this->isCoordsValid($coords)) {
            $this->updated_tiles[] = clone $coords;
        }
    }

    public function getUpdatedTilesData() {
        $data = array();
        foreach ($this->updated_tiles as $coords) {
            $tile = $this->tiles[$coords->x][$coords->y];
            if ($tile == null) {
                $data[] = [
                    "x" => $coords->x,
                    "y" => $coords->y,
                    "owner_id" => null,
                    "trace_owner_id" => null,
                    "residing_player_id" => null
                ];
            } else {
                $data[] = [
                    "x" => $coords->x,
                    "y" => $coords->y,
                    "owner_id" => $tile->getOwner() != null ? $tile->getOwner()->getId() : null,
                    "trace_owner_id" => $tile->getTraceOwner() != null ? $tile->getTraceOwner()->getId() : null,
                    "residing_player_id" => $tile->getResidingPlayer() != null ? $tile->getResidingPlayer()->getId() : null
                ];
            }
        }
        $this->updated_tiles = array();
        return $data;
    }

    public function getAllTilesData() {
        $data = array();
        for ($x = 0; $x < count($this->tiles); $x++) {
            for ($y = 0; $y < count($this->tiles[0]); $y++) { 
                $tile = $this->tiles[$x][$y];
                if ($tile != null) {
                    $data[] = [
                        "x" => $x,
                        "y" => $y,
                        "owner_id" => $tile->getOwner() != null ? $tile->getOwner()->getId() : null,
                        "trace_owner_id" => $tile->getTraceOwner() != null ? $tile->getTraceOwner()->getId() : null,
                        "residing_player_id" => $tile->getResidingPlayer() != null ? $tile->getResidingPlayer()->getId() : null
                    ];
                }
            }
        }
        return $data;
    }

    private function occupyAreaRecursively($coords, $player) {
        $this->occupyTile($coords, $player);
        foreach ($this->getTileNeighboursCoords($coords) as $neighbour_coords) {
            if (!$this->isTileOccupiedBy($neighbour_coords, $player)) {
                $this->occupyAreaRecursively($neighbour_coords, $player);
            }
        }
    }

    private function clearAreaRecursively($coords, $player) {
        $tile = $this->tiles[$coords->x][$coords->y];
        $this->updated_tiles[] = $coords;
        if ($tile->getTraceOwner() == null && $tile->getResidingPlayer() == null) {
            $this->tiles[$coords->x][$coords->y] = null;
        } else {
            $tile->setOwner(null);
        }
        foreach ($this->getTileNeighboursCoords($coords) as $neighbour_coords) {
            if ($this->isTileOccupiedBy($neighbour_coords, $player)) {
                $this->clearAreaRecursively($neighbour_coords, $player);
            }
        }
    }

    public function isTileOccupiedBy($coords, $player) {
        $tile = $this->tiles[$coords->x][$coords->y];
        return $tile != null && $tile->getOwner() === $player;
    }

    private function getTileNeighboursCoords($coords) {
        $neighbours = array();
        $up_neighbour = $coords->copyMovedBy(0, 1);
        $right_neighbour = $coords->copyMovedBy(1, 0);
        $down_neighbour = $coords->copyMovedBy(0, -1);
        $left_neighbour = $coords->copyMovedBy(-1, 0);

        if ($this->isCoordsValid($up_neighbour)) {
            $neighbours[] = $up_neighbour;
        }
        if ($this->isCoordsValid($right_neighbour)) {
            $neighbours[] = $right_neighbour;
        }
        if ($this->isCoordsValid($down_neighbour)) {
            $neighbours[] = $down_neighbour;
        }
        if ($this->isCoordsValid($left_neighbour)) {
            $neighbours[] = $left_neighbour;
        }

        return $neighbours;
    }

    private function getTileNeighboursCoordsInclDiagonal($coords) {
        $neighbours = $this->getTileNeighboursCoords($coords);

        $ne_neighbour = $coords->copyMovedBy(1, 1);
        $se_neighbour = $coords->copyMovedBy(1, -1);
        $sw_neighbour = $coords->copyMovedBy(-1, -1);
        $nw_neighbour = $coords->copyMovedBy(-1, 1);

        if ($this->isCoordsValid($ne_neighbour)) {
            $neighbours[] = $ne_neighbour;
        }
        if ($this->isCoordsValid($se_neighbour)) {
            $neighbours[] = $se_neighbour;
        }
        if ($this->isCoordsValid($sw_neighbour)) {
            $neighbours[] = $sw_neighbour;
        }
        if ($this->isCoordsValid($nw_neighbour)) {
            $neighbours[] = $nw_neighbour;
        }

        return $neighbours;
    }

    private function occupyTile($coords, $player) {
        $tile = $this->tiles[$coords->x][$coords->y];
        if ($tile == null) {
            $tile = new Tile();
            $tile->setOwner($player);
            $this->tiles[$coords->x][$coords->y] = $tile;
            $player->increaseScore();
        } else {
            if ($tile->getOwner() === $player) { // already occupied by the given player
                return;
            }
            if ($tile->getOwner() != null) { // occupied by other player
                $tile->getOwner()->decreaseScore();
            }
            $tile->setOwner($player);
            $tile->setTraceOwner(null);
            $player->increaseScore();
        }

        $this->updated_tiles[] = clone $coords;
    }

    private function findFirstInnerTile($player) { // return coords of a tile that is supposed to be inside players area, but it is noy yet marked as occupied by that given player
        for ($i = 0; $i < count($player->getTrace()); $i++) {
            $coords = $player->getTrace()[$i];
            if ((!($this->isCoordsValid($coords->copyMovedBy(-1, 0))) || !($this->isTileOccupiedBy($coords->copyMovedBy(-1, 0), $player))) &&
                (!($this->isCoordsValid($coords->copyMovedBy(1, 0))) || !($this->isTileOccupiedBy($coords->copyMovedBy(1, 0), $player)))
            ) {
                $reached_wall = true;
                $check_c = $coords->copyMovedBy(-1, 0); // coords that will be moved left until board wall or a tile occupied by the given player is reached
                while ($check_c->x >= 0) {
                    if ($this->isTileOccupiedBy($check_c, $player)) {
                        $reached_wall = false;
                        break;
                    }
                    $check_c->moveBy(-1, 0);
                }
                if ($reached_wall) { // $check_c reached the wall by moving to the left => right neighbour must be an inner tile
                    return $coords->copyMovedBy(1, 0);
                } else { // $check_c hit a tile occupied by the given player by moving to the left => no conclusion can be made yet, moving right must be examined
                    $reached_wall = true;
                    $check_c = $coords->copyMovedBy(1, 0); // coords that will be moved right until board wall or a tile occupied by the given player is reached
                    while ($check_c->x < count($this->tiles)) {
                        if ($this->isTileOccupiedBy($check_c, $player)) {
                            $reached_wall = false;
                            break;
                        }
                        $check_c->moveBy(1, 0);
                    }
                    if ($reached_wall) {  // $check_c reached the wall by moving to the right => left neighbour must be an inner tile
                        return $coords->copyMovedBy(-1, 0);
                    } else { // $check_c hit a tile occupied by the given player by moving both to the left and to the right => no conclusion can be made from $coords tile
                        continue;
                    }
                }
            } 
            else if ((!($this->isCoordsValid($coords->copyMovedBy(0, -1))) || !($this->isTileOccupiedBy($coords->copyMovedBy(0, -1), $player))) &&
                     (!($this->isCoordsValid($coords->copyMovedBy(0, 1))) || !($this->isTileOccupiedBy($coords->copyMovedBy(0, 1), $player)))
            ) {
                $reached_wall = true;
                $check_c = $coords->copyMovedBy(0, -1); // coords that will be moved downwards until board wall or a tile occupied by the given player is reached
                while ($check_c->y >= 0) {
                    if ($this->isTileOccupiedBy($check_c, $player)) {
                        $reached_wall = false;
                        break;
                    }
                    $check_c->moveBy(0, -1);
                }
                if ($reached_wall) { // $check_c reached the wall by moving downwards => upper neighbour must be an inner tile
                    return $coords->copyMovedBy(0, 1);
                } else { // $check_c hit a tile occupied by the given player by moving downwards => no conclusion can be made yet, moving upwards must be examined
                    $reached_wall = true;
                    $check_c = $coords->copyMovedBy(0, 1); // coords that will be moved upwards until board wall or a tile occupied by the given player is reached
                    while ($check_c->y < count($this->tiles[0])) {
                        if ($this->isTileOccupiedBy($check_c, $player)) {
                            $reached_wall = false;
                            break;
                        }
                        $check_c->moveBy(0, 1);
                    }
                    if ($reached_wall) {  // $check_c reached the wall by moving upwards => down neighbour must be an inner tile
                        return $coords->copyMovedBy(0, -1);
                    } else { // $check_c hit a tile occupied by the given player by moving both downwards and upwards => no conclusion can be made from $coords tile
                        continue;
                    }
                }
            }
        }

        return null; // there is no inner tile that is not already makred as occupied
    }

}

?>