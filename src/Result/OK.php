<?php
namespace w3ocom\FieldsPack\Result;

class OK implements Any {

    public function getArr() {
        return [];
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
        return '';
    }
}
