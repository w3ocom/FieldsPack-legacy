<?php
namespace w3ocom\FieldsPack\Result;

class Err implements Any {
    protected $err = 'Undefined error';

    public function __construct($err = '') {
        if ($err) {
            $this->setErr($err);
        }
    }
    
    public function setErr($err) {
        $this->err = $err;
    }

    public function getErr() {
        return $this->err;
    }

    public function getStr() {
        throw new \LogicException("Result is not string, it is error: " . $this->err);
    }

    public function getArr() {
        throw new \LogicException("Result is not array, it is error: " . $this->err);
    }
    
    public function isErr() {
        return true;
    }

    public function isArr() {
        return false;
    }

    public function __toString() {
        return $this->err;
    }
}
