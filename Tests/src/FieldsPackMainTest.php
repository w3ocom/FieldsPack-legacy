<?php

namespace Test\w3ocom\FieldsPack;

use w3ocom\FieldsPack\FieldsPackMain;

class FieldsPackMainTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var FieldsPackMain
     */
    protected $object;
    
    protected $is_opt_mode = false;

    protected function setUp(): void {
        $this->object = new FieldsPackMain();
    }
    
    /*
     * @covers w3ocom\FieldsPack\FieldsPackOpt::__construct
     */
    public function testConstruct(): void {
        $obj = new FieldsPackMain("ntest");
        $this->assertEquals("\0\0", $obj->pack(['test' => 0]));
        
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
    
    public function goodAndBadVariantsProvider() {
        return
            $this->goodVariantsProvider() + $this->badVariantsProvider();
    }
    
    /**
     * @dataProvider goodAndBadVariantsProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::unpackFmtParse
     * @todo   Implement testUnpackFmtParse().
     */
    public function testUnpackFmtParse($fields_un, $arr) {
        $this->assertEquals($arr, $this->object->UnpackFmtParse($fields_un));
    }

        
    /**
     * @covers w3ocom\FieldsPack\FieldsPackMain::setFields
     */
    public function testSetFields() {
        $this->assertFalse($this->object->SetFields("ntest"));

        // bad fields srting expected
        $this->assertTrue(is_string(
                $this->object->setFields('/*')
        ));
        
        if ($this->is_opt_mode) {
            // Good _f
            $this->assertFalse($this->object->SetFields("C_f/None/ntwo"));

            // Bad format _f
            $this->assertTrue(is_string(
                $this->object->SetFields("X_f/None/ntwo")
            ));

            // too many fields
            $this->assertTrue(is_string(
                $this->object->SetFields("C_f/Nf0/nf1/Cf2/nf3/Cf4/Cf5/Cf6/Cf7/Cf8")
            ));

            // OK
            $this->assertFalse(is_string(
                $this->object->SetFields("C_f/Nf0/nf1/Cf2/nf3/Cf4/Cf5/Cf6/Cf7")
            ));
        }
        
        // *-mode
        $this->assertFalse($this->object->SetFields("n*one/C*two"));

        // Bad-fmt-* 
        $this->assertTrue(is_string(
            $this->object->SetFields("n*one/X*two")
        ));

    }
    
    /**
     * @dataProvider goodVariantsProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::packFmtFields
     */
    public function testPackFmtFields($fields_un, $fields_arr) {
        $this->assertEquals($fields_un, $this->object->PackFmtFields($fields_arr));
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
            [ "C*x",
                chr(0) . 'test',
                'test'
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
        }
        
        return $arr;
    }
    
    /**
     * @dataProvider packUnpackProvider
     * @covers w3ocom\FieldsPack\FieldsPackMain::pack
     */
    public function testPack($fields_un, $pk, $arr) {
        $this->object->setFields($fields_un);
        $this->assertEquals($pk, $this->object->pack($arr));
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
        if ($arr === 'test') {
            $arr = ['x' => '', '*' => $arr];
        } elseif (is_array($arr) && !isset($arr['*'])) {
            $arr['*'] = '';
        }
        $this->assertEquals($arr, $this->object->unpack($pk));
    }
    
    /*
     * @covers w3ocom\FieldsPack\FieldsPackOpt::unpack
     */
    public function testUnpackIncH() {
        $this->object->setFields('Cf1/Cf2/Cf3');
        $pk = $this->object->pack(['f2' => 2, 'f3' => 3]);
        $this->assertEquals(chr(0) . chr(2) . chr(3), $pk);
        // set inc_h
        $this->object->inc_h = true;
        $un = $this->object->unpack($pk);
        $this->assertArrayHasKey('_h', $un);
    }
}
