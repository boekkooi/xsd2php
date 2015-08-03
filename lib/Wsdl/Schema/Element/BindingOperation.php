<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tBindingOperation
 */
class BindingOperation
{
    /**
     * @property string $name
     */
    private $name;

    /**
     * @var BindingOperationMessage $input
     */
    private $input = null;

    /**
     * @var BindingOperationMessage $output
     */
    private $output = null;

    /**
     * @var BindingOperationFault[] $fault
     */
    private $fault = null;

    /**
     * BindingOperation constructor.
     *
     * @param string $name
     * @param BindingOperationMessage|null $input
     * @param BindingOperationMessage|null $output
     * @param BindingOperationFault[] $fault
     */
    public function __construct($name, BindingOperationMessage $input = null, BindingOperationMessage $output = null, array $fault = [])
    {
        $this->name = $name;
        $this->input = $input;
        $this->output = $output;
        $this->fault = $fault;
    }

    /**
     * Gets as name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets as input
     *
     * @return BindingOperationMessage
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Gets as output
     *
     * @return BindingOperationMessage
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Adds as fault
     *
     * @param BindingOperationFault $fault
     * @return self
     */
    public function addFault(BindingOperationFault $fault)
    {
        $this->fault[] = $fault;
        return $this;
    }

    /**
     * Gets as fault
     *
     * @return BindingOperationFault[]
     */
    public function getFault()
    {
        return $this->fault;
    }
}
