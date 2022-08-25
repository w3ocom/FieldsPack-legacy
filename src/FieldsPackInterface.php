<?php

namespace w3ocom\FieldsPack;

interface FieldsPackInterface
{
    public function pack($arr);
    public function unpack($_raw);

    public function setFields($fields_un);

    public static function unpackFmtParse($fields_un);
    public static function packFmtFields($fields_arr);
}
