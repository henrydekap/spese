<?php

namespace App\Entity;

class SpeseType {

    private $code;
    private $name;

    private $monthly;
    private $yearly;
    private $entries;

    public function __construct (string $code, string $name, float $month, float $year, int $entries) {
        $this->code = $code;
        $this->name = $name;
        $this->monthly = $month;
        $this->yearly = $year;
        $this->entries = $entries;
    }

    public function getCode() {
        return $this->code;
    }

    public function getName() {
        return $this->name;
    }

    public function getMonthToDate() {
        return $this->monthly;
    }

    public function getYearToDate() {
        return $this->yearly;
    }

    public function getEntries() {
        return $this->entries;
    }

}