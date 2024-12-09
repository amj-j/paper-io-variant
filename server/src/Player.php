<?php

require_once "Coords.php";

class Player {
    private $connection;
    private $id;
    private $name;
    private $color;
    private $position;
    private $direction;
    private $trace = array();
    private $score = 0;
    private $requested_direction;

    public function __construct($connection, $id, $name, $color, $position, $direction) {
        $this->connection = $connection;
        $this->id = $id;
        $this->name = $name;
        $this->color = $color;
        $this->position = $position;
        $this->direction = $direction;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function setConnection($connection) {
        $this->connection = $connection;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getColor() {
        return $this->color;
    }

    public function getPosition() {
        return $this->position;
    }

    public function setPosition($coords) {
        $this->position = $coords;
    }

    public function move() {
        $this->position->moveBy($this->direction->x, $this->direction->y);
    }

    public function getDirection() {
        return $this->direction;
    }

    public function setRequestedDirection($coords) {
        $this->requested_direction = $coords;
    }

    public function updateDirection() {
        if ($this->requested_direction != null) {
            $this->direction = $this->requested_direction;
            $this->requested_direction = null;  
        }
    }

    public function getTrace() {
        return $this->trace;
    }

    public function addToTrace($coords) {
        $this->trace[] = clone $coords;
    }

    public function deleteTrace() {
        $this->trace = array();
    }

    public function increaseScore() {
        $this->score++;
    }

    public function decreaseScore() {
        $this->score--;
    }

    public function getScore() {
        return $this->score;
    }

    public function setScore($score) {
        $this->score = $score;
    }

}

?>