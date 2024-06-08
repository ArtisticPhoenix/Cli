<?php
use PHPUnit\Framework\TestCase;


/**
 *
 * (c) 2016 Hugh Durham III
 *
 * For license information please view the LICENSE file included with this source code.
 *
 * Debug class - circular refrence safe
 *
 * @author HughDurham {ArtisticPhoenix}
 * @package Evo
 * @subpackage debug
 *
 */
class DebugTest extends TestCase{

    /*
    /**
     *
     * @var Debug
     *//*
    protected $Debug;
    
    /**
     *
     *//*
    public function setup()
    {
        $this->Debug = Debug::getInstance('UnitTest');
        $this->Debug->setHtmlOutput(false);
        $this->Debug->setDepthLimit(4);
        $this->Debug->setFlags(Debug::SHOW_ALL);
    }
    
    /**
     *
     * @group DebugTest
     * @group testBoolean
     *//*
    public function testBoolean()
    {
        $this->assertEquals('bool(false)', $this->Debug->varExport(false));
        $this->assertEquals('bool(true)', $this->Debug->varExport(true));
    }
    */
}