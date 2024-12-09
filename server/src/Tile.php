<?php

    class Tile {
        private $owner = null;
        private $trace_owner = null;
        private $residing_player = null;

        public function getOwner() {
            return $this->owner;
        }
    
        public function setOwner($player) {
            $this->owner = $player;
        }
    
        public function getTraceOwner() {
            return $this->trace_owner;
        }

        public function setTraceOwner($player) {
            $this->trace_owner = $player;
        }

        public function getResidingPlayer() {
            return $this->residing_player;
        }

        public function setResidingPlayer($player) {
            $this->residing_player = $player;
        }
    }

?>