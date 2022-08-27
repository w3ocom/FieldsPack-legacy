<?php

namespace w3ocom\FieldsPack\Result;

interface Any {
    public function isErr();//: bool;
    public function getErr();//: string;
    public function getArr();//: array;
    public function getStr();//: string;
}
