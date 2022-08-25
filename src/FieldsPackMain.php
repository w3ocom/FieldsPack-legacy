<?php
namespace w3ocom\FieldsPack;

class FieldsPackMain implements FieldsPackInterface
{
    public $fixed_len = false;
    public $_ext_arr = []; // only for non-opti mode
    public $fields_pk = false;
    public $fields_un = false;
    public $fields = [];
    
    public $inc_h = false; // include add-fields to results for unpackArr

    public function __construct($fields_un = false)
    {
        if (is_string($fields_un)) {
            $err = $this->setFields($fields_un);
            if ($err) {
                throw new \Exception($err);
            }
        }
    }

    public function setFields($fields_un, $last_field = 'a**')
    {
        // parse fields to array
        $fmt_arr = self::unpackFmtParse($fields_un);
        if (is_string($fmt_arr)) {
            return "Bad fields-format: $fields_un";
        }
        $this->fields = $fmt_arr;


        // calculate header_bytes
        $fcnt = count($fmt_arr);
        $fmt = reset($fmt_arr);
        
        // Only for FieldsPackOpt ext
        if (isset($this->header_name) && (key($fmt_arr) === $this->header_name)) {
            // @codeCoverageIgnoreStart
            $this->header_bytes = self::fmtMultiBytesLen($fmt);
            if (!$this->header_bytes) {
                return "Bad '{$this->header_name}' field format: $fmt";
            }
            if (--$fcnt > (8 * $this->header_bytes)) {
                return "Too many fields";
            }
            // @codeCoverageIgnoreEnd
        }

        // caclulate fixed_len, fields_un, fields_pk, _ext_arr
        $pk = [];
        $_ext_arr = [];
        $_ext_un = [];
        $fixed_len = 0;
        foreach ($fmt_arr as $name => $fmt) {
            if ('*' === substr($fmt, -1)) {
                $_ext_arr[] = $name;
                $fmt = substr($fmt, 0, -1);
                $len = self::fmtMultiBytesLen($fmt);
            } else {
                $st = pack($fmt, 0);
                $len = strlen($st);
            }
            if (!$len) {
                return "Bad format: $fmt";
            }
            $fixed_len += $len;
            $pk[] = $fmt;
            $_ext_un[] = $fmt . $name;
        }
        $this->fields_pk = implode('', $pk);
        $this->fixed_len = $fixed_len;
        if ($last_field) {
            $_ext_un[] = $last_field; //'a**';
        }
        $this->fields_un = implode('/', $_ext_un);
        $this->_ext_arr = $_ext_arr;

        return false;
    }

    /**
     * Convert header-fmt-char to pack-bytes-length
     *
     * @staticvar array $bl
     * @param string $fmtChar
     * @return false|integer
     */
    public static function fmtMultiBytesLen($fmtChar)
    {
        static $fmtCharToLen = [
            'J' => 8,
            'Q' => 8,
            'P' => 8,
            'N' => 4,
            'L' => 4,
            'V' => 4,
            'l' => 4,
            'n' => 2,
            'v' => 2,
            'C' => 1,
        ];
        return isset($fmtCharToLen[$fmtChar]) ? $fmtCharToLen[$fmtChar] : false;
    }

    /**
     * Convert unpack-format string to format-array
     *
     * Return:
     *  array = success. contain field items [name]=>pack-format
     *  string = error
     *
     * @param string $fields_un
     * @return string|array
     */
    public static function unpackFmtParse($fields_un)
    {
        $nms = [];
        $arr = empty($fields_un) ? [] : explode('/', $fields_un);
        foreach($arr as $f_n) {
            $p = 0;
            while (++$p <= strlen($f_n)) {
                $ch = substr($f_n, $p, 1);
                if (!is_numeric($ch) && ($ch !== '*')) {
                    $name = substr($f_n, $p);
                    if (!strlen($name)) {
                        return "Empty field name";
                    }
                    $nms[$name] = substr($f_n, 0, $p);
                    break;
                }
            }
        }
        if (count($nms) === count($arr)) {
            return $nms;
        }
        return "Error unpack-format parsing";
    }

    /**
     * Back-function for unpackFmtParse
     *
     * In: Array with fields
     * Out: packed-string
     *
     * @param array $fields_arr
     * @return string
     */
    public static function packFmtFields($fields_arr)
    {
        $arr = [];
        foreach($fields_arr as $name => $fmt) {
            $arr[] = $fmt . $name;
        }
        return implode('/', $arr);
    }

    public function pack($arr)
    {
        if (!is_array($arr)) {
            $arr = ['*' => $arr];
        }
        $wr_arr = [];
        $ex_str = '';
        foreach($this->fields as $name => $fmt) {
            if (substr($fmt, 1, 1) === '*') {
                $ex_str .= $v = isset($arr[$name]) ? $arr[$name] : '';
                $wr_arr[] = pack(substr($fmt, 0, 1), strlen($v));
            } else {
                $v = isset($arr[$name]) ? $arr[$name] : 0;
                $wr_arr[] = pack($fmt, $v);
            }

        }
        $ext = isset($arr['*']) ? $arr['*'] : '';
        return implode('', $wr_arr) . $ex_str . $ext;
    }

    public function unpack($_raw)
    {
        $l = strlen($_raw);
        if ($l < $this->fixed_len) {
            return "String too short";
        }
        //$fix_str = substr($_raw, 0, $this->fixed_len);
        $arr = unpack($this->fields_un, $_raw);
        foreach($this->_ext_arr as $name) {
            $len = $arr[$name];
            $arr[$name] = substr($arr['*'], 0, $len);
            $arr['*'] = substr($arr['*'], $len);
        }
        if ($this->inc_h) {
            $_fmt = $this->fields_un;
            $arr['_h'] = compact('_fmt', '_raw');
        }
        return $arr;
    }
}