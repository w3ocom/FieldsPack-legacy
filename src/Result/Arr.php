<?php
namespace w3ocom\FieldsPack\Result;

class Arr implements Any {
    protected $data;
    
    public function __construct($arr) {
        $this->setArr($arr);
    }
    
    public function setArr($arr) {
        $this->data = $arr;
    }

    public function getArr() {
        return $this->data;
    }
    
    public function isErr() {
        return false;
    }

    public function isArr() {
        return true;
    }


    public function getErr() {
        return '';
    }
    
    public function getStr() {
        throw new LogicException("Result is array");
    }
}
