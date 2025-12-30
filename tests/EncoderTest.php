<?php
use PHPUnit\Framework\TestCase;
use BittyTorrent\Torrent\Encoder;

class EncoderTest extends TestCase {
    public function testEncodeString() {
        $this->assertEquals('4:spam', Encoder::encode('spam'));
    }
    public function testEncodeInt() {
        $this->assertEquals('i3e', Encoder::encode(3));
    }
    public function testEncodeList() {
        $this->assertEquals('l4:spami3ee', Encoder::encode(['spam', 3]));
    }
    public function testEncodeDict() {
        // Integer 42 should be i42e
        $this->assertEquals('d3:bar4:spam3:fooi42ee', Encoder::encode(['bar'=>'spam', 'foo'=>42]));
    }
}
