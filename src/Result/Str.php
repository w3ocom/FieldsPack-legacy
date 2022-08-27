<?php
namespace w3ocom\FieldsPack\Result;

class Str implements Any {
    protected $data;
    
    public function __construct($arr) {
        $this->setStr($arr);
    }
    
    public function setStr($str) {
        $this->data = $str;
    }

    public function getArr() {
        throw new LogicException("Result is string, not array");
    }
    
    public function isErr() {
        return false;
    }
    
    public function isArr() {
        return false;
    }

    public function getErr() {
        return '';
    }
    
    public function getStr() {
        return $this->data;
    }
}
