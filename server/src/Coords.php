<?php

class Coords {
    public $x;
    public $y;

    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function moveBy($horizontal, $vertical) {
        $this->x += $horizontal;
        $this->y += $vertical;
    }

    public function copyMovedBy($horizontal, $vertical) {
        return new Coords($this->x + $horizontal, $this->y + $vertical);
    }

    public function compare($coords) {
        return $this->x == $coords->x && $this->y == $coords->y;
    }
}

?>