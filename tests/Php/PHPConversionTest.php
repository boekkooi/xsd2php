<?php
namespace Tests\Goetas\Xsd\XsdToPhp\Php;

use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Php\PhpConverter;
use Goetas\XML\XSDReader\SchemaReader;
use Goetas\Xsd\XsdToPhp\Code\Naming\ShortNamingStrategy;

class PHPConversionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $xml
     * @return \Zend\Code\Generator\ClassGenerator[]
     */
    protected function getClasses($xml)
    {
        if (!is_array($xml)) {
            $xml = [ 'schema.xsd' => $xml ];
        }

        $reader = new SchemaReader();
        $schemas = [];
        foreach ($xml as $name => $str) {
            $schemas[] = $reader->readString($str, $name);
        }

        $config = new Configuration();
        $config->setNamingStrategy(new ShortNamingStrategy());
        $config->addNamespace('http://www.example.com', 'Example');

        $phpcreator = new PhpConverter($config);
        $items = $phpcreator->convert($schemas);

        $classes = array();
        foreach ($items as $k => $item) {
            $classes[$k] = $item;
        }
        return $classes;
    }

    public function testSimpleContent()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:simpleContent>
    					<xs:extension base="xs:string">
    						<xs:attribute name="code" type="xs:string"/>
    					</xs:extension>
				    </xs:simpleContent>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];

        $this->assertTrue($codegen->hasMethod('value'));
        $this->assertTrue($codegen->hasMethod('__construct'));
        $this->assertTrue($codegen->hasMethod('__toString'));

        $this->assertTrue($codegen->hasMethod('getCode'));
        $this->assertTrue($codegen->hasMethod('setCode'));
    }

    public function testSimpleNoAttributesContent()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:simpleContent>
    					<xs:extension base="xs:string"/>
				    </xs:simpleContent>
                </xs:complexType>
                <xs:simpleType name="double">
                    <xs:restriction base="xs:string"/>
                </xs:simpleType>
            </xs:schema>';

        $items = $this->getClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];

        $this->assertTrue($codegen->hasMethod('value'));
        $this->assertTrue($codegen->hasMethod('__construct'));
        $this->assertTrue($codegen->hasMethod('__toString'));
    }

    public function testNoMulteplicity()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="xs:long" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getClasses($xml);
        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];
        $this->assertFalse($codegen->hasMethod('setIds'));
        $this->assertFalse($codegen->hasMethod('getIds'));

        $this->assertTrue($codegen->hasMethod('getId'));
        $this->assertTrue($codegen->hasMethod('setId'));
    }

    public function testMulteplicity()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="ary" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="ary">
                    <xs:all>
                        <xs:element name="id" type="xs:long" maxOccurs="2"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getClasses($xml);

        $this->assertCount(1, $items);

        $codegen = $items['Example\SingleType'];

        $this->assertTrue($codegen->hasMethod('setIds'));
        $this->assertTrue($codegen->hasMethod('getIds'));

        $this->assertTrue($codegen->hasMethod('addId'));
        $this->assertTrue($codegen->hasMethod('setId'));
        $this->assertTrue($codegen->hasMethod('removeId'));
        $this->assertTrue($codegen->hasMethod('indexOfId'));
    }

    public function testNestedMulteplicity()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="ary" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="ary">
                    <xs:all>
                        <xs:element name="idA" type="ary2" maxOccurs="2"/>
                    </xs:all>
                </xs:complexType>
                <xs:complexType name="ary2">
                    <xs:all>
                        <xs:element name="idB" type="xs:long" maxOccurs="2"/>
                    </xs:all>
                </xs:complexType>
            </xs:schema>';

        $items = $this->getClasses($xml);

        $this->assertCount(2, $items);

        $single = $items['Example\SingleType'];
        $this->assertTrue($single->hasMethod('addId'));
        $this->assertTrue($single->hasMethod('setId'));
        $this->assertTrue($single->hasMethod('removeId'));
        $this->assertTrue($single->hasMethod('indexOfId'));

        $this->assertTrue($single->hasMethod('getIds'));
        $this->assertTrue($single->hasMethod('setIds'));

        $ary = $items['Example\Ary2Type'];
        $this->assertTrue($ary->hasMethod('addIdB'));
        $this->assertTrue($ary->hasMethod('setIdB'));
        $this->assertTrue($ary->hasMethod('removeIdB'));
        $this->assertTrue($ary->hasMethod('indexOfIdB'));

        $this->assertTrue($ary->hasMethod('getIdBs'));
        $this->assertTrue($ary->hasMethod('setIdBs'));
    }

    public function testMultipleArrayTypes()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:complexType name="ArrayOfStrings">
                    <xs:all>
                        <xs:element name="string" type="xs:string" maxOccurs="unbounded"/>
                    </xs:all>
                </xs:complexType>

                <xs:complexType name="Single">
                    <xs:all>
                        <xs:element name="a" type="ArrayOfStrings"/>
                        <xs:element name="b" type="ArrayOfStrings"/>
                    </xs:all>
                </xs:complexType>

            </xs:schema>';

        $items = $this->getClasses($xml);

        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];

        $this->assertTrue($single->hasMethod('addA'));
        $this->assertTrue($single->hasMethod('setAs'));

        $this->assertTrue($single->hasMethod('addB'));
        $this->assertTrue($single->hasMethod('setBs'));
    }

    public function testSimpleMulteplicity()
    {
        $xml = '
            <xs:schema targetNamespace="http://www.example.com"
            xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="single">
                    <xs:all>
                        <xs:element name="id" type="ary" minOccurs="0"/>
                    </xs:all>
                </xs:complexType>
                <xs:simpleType name="ary">
                    <xs:list itemType="xs:integer" />
                </xs:simpleType>
            </xs:schema>';

        $items = $this->getClasses($xml);

        $this->assertCount(1, $items);

        $single = $items['Example\SingleType'];
        $this->assertTrue($single->hasMethod('addId'));
        $this->assertTrue($single->hasMethod('setId'));
        $this->assertTrue($single->hasMethod('removeId'));
        $this->assertTrue($single->hasMethod('indexOfId'));

        $this->assertTrue($single->hasMethod('getIds'));
        $this->assertTrue($single->hasMethod('setIds'));
    }
}
