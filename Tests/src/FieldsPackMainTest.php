<?php

namespace Test\w3ocom\FieldsPack;

use w3ocom\FieldsPack\FieldsPackMain;

class FieldsPackMainTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var FieldsPackMain
     */
    protected $object;
    
    protected $is_opt_mode = false;

    public $tst_class = 'FieldsPackMain';

    public function setUp() {
        $class = "w3ocom\\FieldsPack\\" . $this->tst_class;
        $this->object = new $class();
    }
    
    /*
     * @covers w3ocom\FieldsPack\FieldsPackOpt::__construct
     */
    public function testConstruct() {
        $obj = new FieldsPackMain("ntest");
        $result = $obj->pack(['test' => 0]);
        $this->assertEquals("\0\0", $result->getStr());
        
        $this->expectException(\Exception::class);
        $obj = new FieldsPackMain("n");
    }
    

    /**
     * Provider of format -> length
     * 
     * @return array
     */
    public function formatLenProvider() {
        $arr = [];
        foreach([
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
        ] as $fmtC => $fmtLen) {
            $arr[] = [$fmtC, $fmtLen];
        }
        return $arr;
    }
    
    /**
     * @dataProvider formatLenProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::fmtMultiBytesLen
     */
    public function testFmtMultiBytesLen($fmtC, $fmtLen) {
        $this->assertEquals($fmtLen, FieldsPackMain::FmtMultiBytesLen($fmtC));
    }

    public function goodVariantsProvider() {
        return [
            'ntest' => 
                ['ntest', [
                    'test' => 'n'
                ]],

            'Ca/Cb/Cc' =>
                ['Ca/Cb/Cc', [
                    'a' => 'C',
                    'b' => 'C',
                    'c' => 'C',
                ]],

            'NNN/nnn/CCC/JJJ' =>
                ['NNN/nnn/CCC/JJJ', [
                    'NN' => 'N',
                    'nn' => 'n',
                    'CC' => 'C',
                    'JJ' => 'J',
                ]]
            ];
    }
    
    public function badVariantsProvider() {
        return [
            'EmptyNameTest' => 
                ['Ctest/n', "Empty field name"],

            'BadFieldsCount' =>
                ['Ca//Cb', 'Error unpack-format parsing'],
        ];
    }
    
    /**
     * @dataProvider goodVariantsProvider
     * @dataProvider badVariantsProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::unpackFmtParse
     */
    public function testUnpackFmtParse($fields_un, $arr_or_err) {
        $result = $this->object->UnpackFmtParse($fields_un);
        if ($result->isErr()) {
            $result = $result->getErr();
        } else {
            $result = $result->getArr();
    }
        $this->assertEquals($arr_or_err, $result);
    }
    
    /**
     * @dataProvider goodVariantsProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::packFmtFields
     */
    public function testPackFmtFields($fields_un, $fields_arr_or_err) {
        if (is_array($fields_arr_or_err)) {
            $result = $this->object->PackFmtFields($fields_arr_or_err);
            $this->assertFalse($result->isErr());
            $this->assertEquals($fields_un, $result->getStr());
        }
    }

        
    /**
     * @covers w3ocom\FieldsPack\FieldsPackMain::setFields
     */
    public function testSetFields() {
        $result = $this->object->SetFields('ntest');
        $this->assertFalse($result->isErr());

        // bad fields srting expected
        $result = $this->object->setFields('/*');
        $this->assertTrue($result->isErr());
        
        if ($this->is_opt_mode) {
            // Good _f
            $result = $this->object->SetFields("C_f/None/ntwo");
            $this->assertFalse($result->isErr());

            // Bad format X_f
            $result = $this->object->SetFields("X_f/None/ntwo");
            $this->assertTrue($result->isErr());

            // too many fields (max 8)
            $result = $this->object->SetFields("C_f/Nf0/nf1/Cf2/nf3/Cf4/Cf5/Cf6/Cf7/Cf8");
            $this->assertTrue($result->isErr());

            // OK
            $result = $this->object->SetFields("C_f/Nf0/nf1/Cf2/nf3/Cf4/Cf5/Cf6/Cf7");
            $this->assertFalse($result->isErr());
        }
        
        // *-mode
        $result = $this->object->SetFields('n*one/C*two');
        $this->assertFalse($result->isErr());

        // Bad-fmt-* 
        $result = $this->object->SetFields('n*one/X*two');
        $this->assertTrue($result->isErr());

    }
    

    public function packUnpackProvider() {
        $arr = [
            [ "Cone", 
                chr(123) . chr(111),
                [
                    'one' => 123,
                    '*' => chr(111)
                ]
            ],
            [ "C*one",
                chr(1) . 'a',
                [
                    'one' => 'a'
                ]
            ],
        ];

        if ($this->is_opt_mode) {
            $arr[] =
            [ 'C_f/Cone',
                chr(1) . chr(2),
                [
                    'one' => 2
                ]
            ];
            $arr[] =
            [ 'C_f/Cf1',
                chr(1) . chr(2) . chr(3),
                [
                    'f1' => 2,
                    '*' => chr(3)
                ]
            ];
            $arr[] =
            [ 'C_f/C*str',
                chr(1) . chr(2) . chr(3) . chr(4),
                [
                    'str' => chr(3) . chr(4),
                ]
            ];

        }
        
        return $arr;
    }
    
    /**
     * @dataProvider packUnpackProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::pack
     */
    public function testPack($fields_un, $pk, $arr) {
        $this->object->setFields($fields_un);
        $result = $this->object->pack($arr);
        $this->assertFalse($result->isErr());
        $this->assertEquals($pk, $result->getStr());
    }

    public function unpackBadProvider() {
        return [
            ['Cone/Ctwo',
                chr(1),
                "String too short"
            ]
        ];
    }

    /**
     * @dataProvider unpackBadProvider
     * @dataProvider packUnpackProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::unpack
     */
    public function testUnpack($fields_un, $pk, $arr) {
        $this->object->setFields($fields_un);
        
        $result = $this->object->unpack($pk);

        if (is_array($arr)) {
            if (!isset($arr['*'])) {
            $arr['*'] = '';
        }
            $this->assertFalse($result->isErr());
            $this->assertEquals($arr, $result->getArr());
        } else {
            $this->assertTrue($result->isErr());
            $this->assertEquals($arr, $result->getErr());
        }
    
    }
    
    /*
     * @covers w3ocom\FieldsPack\FieldsPackOpt::unpack
     */
    public function testUnpackIncH() {
        $this->object->setFields('Cf1/Cf2/Cf3');
        $result = $this->object->pack(['f2' => 2, 'f3' => 3]);
        $this->assertFalse($result->isErr());
        $pk = $result->getStr();
        $this->assertEquals(chr(0) . chr(2) . chr(3), $pk);
        // set inc_h
        $this->object->inc_h = true;
        $result = $this->object->unpack($pk);
        $this->assertFalse($result->isErr());
        $un = $result->getArr();
        $this->assertArrayHasKey('_h', $un);
    }
}
