<?php

namespace w3ocom\FieldsPack;

class FieldsPackOpt extends FieldsPackMain
{
    public $header_bytes = 0; // how many bytes in _f field (or 0 if _f not exist)
    public $header_name = '_f';

    public function pack($arr)
    {
        if (!$this->header_bytes) {
            return parent::pack($arr);
        }
        
        $ex_str = '';
        $wr_arr = [];
        $bit = 1;
        $mask = 0;
        $mask_fmt = reset($this->fields);
        if (key($this->fields) !== $this->header_name) {
            return new Result\Err("First field name must have name " . $this->header_name);
        }
        while($fmt = next($this->fields)) {
            $name = key($this->fields);
            if (isset($arr[$name])) {
                $v = $arr[$name];
                if (substr($fmt, 1, 1) === '*') {
                    $wr_arr[] = pack(substr($fmt, 0, 1), strlen($v));
                    $ex_str .= $v;
                } else {
                    $wr_arr[] = pack($fmt, $v);
                }
                $mask += $bit;
            }
            $bit *= 2;
        }
        if (isset($arr['*'])) {
            $ext = $arr['*'];
            unset($arr['*']);
        } else {
            $ext = '';
        }
        if (count($wr_arr) != count($arr)) {
            // additional check because values may contain NULL
            foreach($arr as $name => $v) {
                if (isset($this->fields[$name])) continue;
                return new Result\Err("Pack error: Unknown field '$name'");
            }
        }
        return new Result\Str(pack($mask_fmt, $mask) . implode('', $wr_arr) . $ex_str . $ext);
    }

    public function unpack($_raw)
    {
        if (!$this->header_bytes) {
            return parent::unpack($_raw);
        }

        if (!is_string($_raw) || strlen($_raw) < $this->header_bytes) {
            return new Result\Err("Illegal raw_data");
        }

        $mask_fmt = reset($this->fields);
        $mask = unpack($mask_fmt, substr($_raw, 0, $this->header_bytes))[1];

        $fmt = next($this->fields);
        $recfmt = [];
        $_ext_arr = [];
        $up_bit = 2 ** (8 * $this->header_bytes);
        for($bit = 1; $bit < $up_bit; $bit *= 2) {
            if (false === $fmt) break;
            if ($mask & $bit) {
                $name = key($this->fields);
                if (substr($fmt, 1, 1) === '*') {
                    $_ext_arr[] = $name;
                    $fmt = substr($fmt, 0, 1);
                }
                $recfmt[] = $fmt . $name;
            }
            $fmt = next($this->fields);
        }
        $recfmt[] = 'a**';
        $_fmt = implode('/', $recfmt);
        $arr = unpack($_fmt, substr($_raw, $this->header_bytes));
        foreach($_ext_arr as $name) {
            $len = $arr[$name];
            $arr[$name] = substr($arr['*'], 0, $len);
            $arr['*'] = substr($arr['*'], $len);
        }
        if ($this->inc_h) {
            $arr['_h'] = compact('_fmt', '_raw', '_ext_arr');
        }
        return new Result\Arr($arr);
    }
}
