<?php
namespace Tests\Goetas\Xsd\XsdToPhp\Php\PathGenerator;

use Goetas\Xsd\XsdToPhp\Exception\PathGeneratorException;
use Goetas\Xsd\XsdToPhp\Php\PathGenerator\Psr4PathGenerator;

class Psr4PathGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpdir;

    public function setUp(){
        $tmp = sys_get_temp_dir();

        if (is_writable("/dev/shm")) {
            $tmp = "/dev/shm";
        }

        $this->tmpdir = "$tmp/PathGeneratorTest";
        if(!is_dir($this->tmpdir)){
            mkdir($this->tmpdir);
        }
    }

    public function testNoNs()
    {
        $this->setExpectedException(PathGeneratorException::class);
        $generator = new Psr4PathGenerator(array(
            'myns\\' =>$this->tmpdir
        ));
        $generator->getPath('myns2', 'Bar');
    }

    public function testWriterLong()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $path = $generator->getPath('myns\foo', 'Bar');

        $this->assertEquals($this->tmpdir."/foo/Bar.php", $path);
    }

    public function testWriter()
    {
        $generator = new Psr4PathGenerator(array(
            'myns\\' => $this->tmpdir
        ));

        $path = $generator->getPath('myns', 'Bar');

        $this->assertEquals($this->tmpdir."/Bar.php", $path);
    }

    public function testNonExistingDir()
    {
        $this->setExpectedException(PathGeneratorException::class);
        new Psr4PathGenerator(array(
            'myns\\' => "aaaa"
        ));
    }

    public function testInvalidNs()
    {
        $this->setExpectedException(PathGeneratorException::class);
        new Psr4PathGenerator(array(
            'myns' => "aaaa"
        ));
    }
}
