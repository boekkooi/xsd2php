<?php
namespace Goetas\Xsd\XsdToPhp\Jms;

use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Doctrine\Common\Inflector\Inflector;
use Goetas\XML\XSDReader\Schema\Type\BaseComplexType;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Item;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeItem;
use Goetas\XML\XSDReader\Schema\Element\ElementItem;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeContainer;
use Goetas\XML\XSDReader\Schema\Element\ElementContainer;
use Goetas\XML\XSDReader\Schema\Element\ElementDef;
use Goetas\XML\XSDReader\Schema\Element\ElementRef;
use Goetas\XML\XSDReader\Schema\SchemaItem;
use Goetas\Xsd\XsdToPhp\Converter\Configuration;
use Goetas\Xsd\XsdToPhp\Xsd\Converter;
use Goetas\Xsd\XsdToPhp\Xsd\Helper;

class YamlConverter extends Converter
{
    private $classes = [];

    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);

        // Yaml
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "dateTime", "Goetas\\Xsd\\XsdToPhp\\XMLSchema\\DateTime");
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "time", "Goetas\\Xsd\\XsdToPhp\\XMLSchema\\Time");
        $configuration->addTypeAlias("http://www.w3.org/2001/XMLSchema", "date", "DateTime<'Y-m-d'>");
    }

    public function convert(array $schemas)
    {
        $visited = array();
        $this->classes = array();
        foreach ($schemas as $schema) {
            $this->navigate($schema, $visited);
        }
        return $this->getTypes();
    }

    private function flattAttributes(AttributeContainer $container)
    {
        $items = array();
        foreach ($container->getAttributes() as $attr) {
            if ($attr instanceof AttributeContainer) {
                $items = array_merge($items, $this->flattAttributes($attr));
            } else {
                $items[] = $attr;
            }
        }
        return $items;
    }

    private function flattElements(ElementContainer $container)
    {
        $items = array();
        foreach ($container->getElements() as $attr) {
            if ($attr instanceof ElementContainer) {
                $items = array_merge($items, $this->flattElements($attr));
            } else {
                $items[] = $attr;
            }
        }
        return $items;
    }

    /**
     *
     * @return \Goetas\Xsd\XsdToPhp\Php\Structure\PHPClass[]
     */
    public function getTypes()
    {
        uasort($this->classes, function ($a, $b) {
            return strcmp(key($a), key($b));
        });

        $ret = array();

        foreach ($this->classes as $definition) {
            $classname = key($definition["class"]);
            if (strpos($classname, '\\') !== false && (! isset($definition["skip"]) || ! $definition["skip"])) {
                $ret[$classname] = $definition["class"];
            }
        }

        return $ret;
    }

    private function navigate(Schema $schema, array &$visited)
    {
        if (isset($visited[spl_object_hash($schema)])) {
            return;
        }
        $visited[spl_object_hash($schema)] = true;

        foreach ($schema->getTypes() as $type) {
            $this->visitType($type);
        }
        foreach ($schema->getElements() as $element) {
            $this->visitElementDef($schema, $element);
        }

        foreach ($schema->getSchemas() as $schildSchema) {
            if ($this->getConfiguration()->isExcludedNamespace($schildSchema->getTargetNamespace())) {
                continue;
            }

            $this->navigate($schildSchema, $visited);
        }
    }

    private function visitTypeBase(&$class, &$data, Type $type, $name)
    {
        if ($type instanceof BaseComplexType) {
            $this->visitBaseComplexType($class, $data, $type, $name);
        }
        if ($type instanceof ComplexType) {
            $this->visitComplexType($class, $data, $type);
        }
        if ($type instanceof SimpleType) {
            $this->visitSimpleType($class, $data, $type, $name);
        }
    }

    private function &visitElementDef(Schema $schema, ElementDef $element)
    {
        if (! isset($this->classes[spl_object_hash($element)])) {
            $className = $this->findPHPNamespace($element)."\\".$this->getNamingStrategy()->getItemName($element->getName());
            $class = array();
            $data = array();
            $ns = $className;
            $class[$ns] = &$data;
            $data["xml_root_name"] = $element->getName();

            if ($schema->getTargetNamespace()) {
                $data["xml_root_namespace"] = $schema->getTargetNamespace();
            }
            $this->classes[spl_object_hash($element)]["class"] = &$class;

            if (! $element->getType()->getName()) {
                $this->visitTypeBase($class, $data, $element->getType(), $element->getName());
            } else {
                $this->handleClassExtension($class, $data, $element->getType(), $element->getName());
            }
        }
        return $this->classes[spl_object_hash($element)]["class"];
    }

    private function findPHPNamespace(SchemaItem $item)
    {
        return $this->getConfiguration()->getNamespace($item->getSchema()->getTargetNamespace());
    }

    private function findPHPName(Type $type)
    {
        $schema = $type->getSchema();
        if ($alias = $this->getTypeAlias($type, $schema)) {
            return $alias;
        }

        $ns = $this->findPHPNamespace($type);
        $name = $this->getNamingStrategy()->getTypeName($type);

        return $ns . "\\" . $name;
    }

    private function &visitType(Type $type, $force = false)
    {
        if (! isset($this->classes[spl_object_hash($type)])) {
            if ($alias = $this->getTypeAlias($type)) {
                $class = array();
                $class[$alias] = array();

                $this->classes[spl_object_hash($type)]["class"] = &$class;
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            $className = $this->findPHPName($type);

            $class = array();
            $data = array();

            $class[$className] = &$data;

            $this->classes[spl_object_hash($type)]["class"] = &$class;

            $this->visitTypeBase($class, $data, $type, $type->getName());

            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }

            if (!$force && (Helper::isArrayType($type) || Helper::isArrayNestedElement($type))) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
                return $class;
            }
        } elseif ($force) {
            if (!($type instanceof SimpleType) && !$this->getTypeAlias($type)) {
                $this->classes[spl_object_hash($type)]["skip"] = false;
            }
        }
        return $this->classes[spl_object_hash($type)]["class"];
    }

    private function &visitTypeAnonymous(Type $type, $parentName, $parentClass)
    {
        $class = array();
        $data = array();

        $name = $this->getNamingStrategy()->getAnonymousTypeName($parentName);

        $class[key($parentClass) . "\\" . $name] = &$data;

        $this->visitTypeBase($class, $data, $type, $parentName);
        if ($parentName) {
            $this->classes[spl_object_hash($type)]["class"] = &$class;

            if ($type instanceof SimpleType) {
                $this->classes[spl_object_hash($type)]["skip"] = true;
            }
        }
        return $class;
    }

    private function visitComplexType(&$class, &$data, ComplexType $type)
    {
        $schema = $type->getSchema();
        if (! isset($data["properties"])) {
            $data["properties"] = array();
        }
        foreach ($this->flattElements($type) as $element) {
            $data["properties"][Inflector::camelize($element->getName())] = $this->visitElement($class, $schema, $element);
        }
    }

    private function visitSimpleType(&$class, &$data, SimpleType $type, $name)
    {
        if ($restriction = $type->getRestriction()) {
            $parent = $restriction->getBase();
            if ($parent instanceof Type) {
                $this->handleClassExtension($class, $data, $parent, $name);
            }
        } elseif ($unions = $type->getUnions()) {
            foreach ($unions as $i => $unon) {
                $this->handleClassExtension($class, $data, $unon, $name.$i);
                break;
            }
        }
    }

    private function visitBaseComplexType(&$class, &$data, BaseComplexType $type, $name)
    {
        $parent = $type->getParent();
        if ($parent) {
            $parentType = $parent->getBase();
            if ($parentType instanceof Type) {
                $this->handleClassExtension($class, $data, $parentType, $name);
            }
        }

        $schema = $type->getSchema();
        if (! isset($data["properties"])) {
            $data["properties"] = array();
        }
        foreach ($this->flattAttributes($type) as $attr) {
            $data["properties"][Inflector::camelize($attr->getName())] = $this->visitAttribute($class, $schema, $attr);
        }
    }

    private function handleClassExtension(&$class, &$data, Type $type, $parentName)
    {
        if ($alias = $this->getTypeAlias($type)) {
            $property = array();
            $property["expose"] = true;
            $property["xml_value"] = true;
            $property["access_type"] = "public_method";
            $property["accessor"]["getter"] = "value";
            $property["accessor"]["setter"] = "value";
            $property["type"] = $alias;

            $data["properties"]["__value"] = $property;
        } else {
            $extension = $this->visitType($type, true);

            if (isset($extension['properties']['__value']) && count($extension['properties']) === 1) {
                $data["properties"]["__value"] = $extension['properties']['__value'];
            } else {
                if ($type instanceof SimpleType) { // @todo ?? basta come controllo?
                    $property = array();
                    $property["expose"] = true;
                    $property["xml_value"] = true;
                    $property["access_type"] = "public_method";
                    $property["accessor"]["getter"] = "value";
                    $property["accessor"]["setter"] = "value";

                    if ($valueProp = $this->typeHasValue($type, $class, $parentName)) {
                        $property["type"] = $valueProp;
                    } else {
                        $property["type"] = key($extension);
                    }

                    $data["properties"]["__value"] = $property;
                }
            }
        }
    }

    private function visitAttribute(&$class, Schema $schema, AttributeItem $attribute)
    {
        $property = array();
        $property["expose"] = true;
        $property["access_type"] = "public_method";
        $property["serialized_name"] = $attribute->getName();

        $property["accessor"]["getter"] = "get" . Inflector::classify($attribute->getName());
        $property["accessor"]["setter"] = "set" . Inflector::classify($attribute->getName());

        $property["xml_attribute"] = true;

        if ($alias = $this->getTypeAlias($attribute)) {
            $property["type"] = $alias;
        } elseif ($itemOfArray = Helper::getArrayType($attribute->getType())) {
            if ($valueProp = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                $property["type"] = "Goetas\Xsd\XsdToPhp\Jms\SimpleListOf<" . $valueProp . ">";
            } else {
                $property["type"] = "Goetas\Xsd\XsdToPhp\Jms\SimpleListOf<" . $this->findPHPName($itemOfArray) . ">";
            }

            $property["xml_list"]["inline"] = false;
            $property["xml_list"]["entry_name"] = $itemOfArray->getName();
            if ($schema->getTargetNamespace()) {
                $property["xml_list"]["entry_namespace"] = $schema->getTargetNamespace();
            }
        } else {
            $property["type"] = $this->findPHPClass($class, $attribute);
        }
        return $property;
    }

    private function typeHasValue(Type $type, $parentClass, $name)
    {
        $collected = array();
        do {
            if ($alias = $this->getTypeAlias($type)) {
                return $alias;
            } else {
                if ($type->getName()) {
                    $parentClass = $this->visitType($type);
                } else {
                    $parentClass = $this->visitTypeAnonymous($type, $name, $parentClass);
                }
                $props = reset($parentClass);
                if (isset($props['properties']['__value']) && count($props['properties']) === 1) {
                    return $props['properties']['__value']['type'];
                }
            }
        } while (method_exists($type, 'getRestriction') && $type->getRestriction() && $type = $type->getRestriction()->getBase());

        return false;
    }

    /**
     *
     * @param \Goetas\Xsd\XsdToPhp\PHP\Structure\PHPClass $class
     * @param Schema $schema
     * @param ElementItem $element
     * @param boolean $arrayize
     * @return \Goetas\Xsd\XsdToPhp\PHP\Structure\PHPProperty
     */
    private function visitElement(&$class, Schema $schema, ElementItem $element, $arrayize = true)
    {
        $property = array();
        $property["expose"] = true;
        $property["access_type"] = "public_method";
        $property["serialized_name"] = $element->getName();

        if ($schema->getTargetNamespace()) {
            $property["xml_element"]["namespace"] = $schema->getTargetNamespace();
        }

        $property["accessor"]["getter"] = "get" . Inflector::classify($element->getName());
        $property["accessor"]["setter"] = "set" . Inflector::classify($element->getName());
        $t = $element->getType();

        if ($arrayize) {
            if ($itemOfArray = Helper::getArrayNestedElement($t)) {
                if (!$t->getName()) {
                    $classType = $this->visitTypeAnonymous($t, $element->getName(), $class);
                } else {
                    $classType = $this->visitType($t);
                }

                $visited = $this->visitElement($classType, $schema, $itemOfArray, false);

                $property["type"] = "array<" . $visited["type"] . ">";
                $property["xml_list"]["inline"] = false;
                $property["xml_list"]["entry_name"] = $itemOfArray->getName();
                if ($schema->getTargetNamespace()) {
                    $property["xml_list"]["namespace"] = $schema->getTargetNamespace();
                }
                return $property;
            } elseif ($itemOfArray = Helper::getArrayType($t)) {
                if (!$t->getName()) {
                    $visitedType = $this->visitTypeAnonymous($itemOfArray, $element->getName(), $class);

                    if ($prop = $this->typeHasValue($itemOfArray, $class, 'xx')) {
                        $property["type"] = "array<" .$prop . ">";
                    } else {
                        $property["type"] = "array<" . key($visitedType) . ">";
                    }
                } else {
                    $this->visitType($itemOfArray);
                    $property["type"] = "array<" . $this->findPHPName($itemOfArray) . ">";
                }

                $property["xml_list"]["inline"] = false;
                $property["xml_list"]["entry_name"] = $itemOfArray->getName();
                if ($schema->getTargetNamespace()) {
                    $property["xml_list"]["namespace"] = $schema->getTargetNamespace();
                }
                return $property;
            } elseif (Helper::isArrayElement($element)) {
                $property["xml_list"]["inline"] = true;
                $property["xml_list"]["entry_name"] = $element->getName();
                if ($schema->getTargetNamespace()) {
                    $property["xml_list"]["namespace"] = $schema->getTargetNamespace();
                }

                $property["type"] = "array<" . $this->findPHPClass($class, $element) . ">";
                return $property;
            }
        }

        $property["type"] = $this->findPHPClass($class, $element);
        return $property;
    }

    private function findPHPClass(&$class, Item $node)
    {
        $type = $node->getType();

        if ($alias = $this->getTypeAlias($node->getType())) {
            return $alias;
        }
        if ($node instanceof ElementRef) {
            $elementRef = $this->visitElementDef($node->getSchema(), $node->getReferencedElement());
            return key($elementRef);
        }
        if ($valueProp = $this->typeHasValue($type, $class, '')) {
            return $valueProp;
        }
        if (! $node->getType()->getName()) {
            $visited = $this->visitTypeAnonymous($node->getType(), $node->getName(), $class);
        } else {
            $visited = $this->visitType($node->getType());
        }

        return key($visited);
    }
}
