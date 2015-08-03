<?php

namespace Goetas\Xsd\XsdToPhp\Wsdl\Schema\Element;

/**
 * XSD Type: tOperation
 */
class Operation
{
    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string $parameterOrder
     */
    private $parameterOrder;

    /**
     * @var OperationParam|null $input
     */
    private $input;

    /**
     * @var OperationParam|null $output
     */
    private $output;

    /**
     * @var OperationFault|null $fault
     */
    private $fault;

    /**
     * Operation constructor.
     * @param $name
     * @param OperationParam|null $input
     * @param OperationParam|null $output
     * @param OperationFault|null $fault
     * @param string|null $parameterOrder
     */
    public function __construct($name, OperationParam $input = null, OperationParam $output = null, OperationFault $fault = null, $parameterOrder = null)
    {
        $this->name = $name;
        if ($input === null && $output === null) {
            throw new \InvalidArgumentException('A input or output for the operation is required.');
        }

        $this->input = $input;
        $this->output = $output;
        $this->fault = $fault;

        $this->parameterOrder = $parameterOrder;
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
     * Gets as parameterOrder
     *
     * @return string
     */
    public function getParameterOrder()
    {
        return $this->parameterOrder;
    }

    /**
     * Gets as input
     *
     * @return OperationParam
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Gets as output
     *
     * @return OperationParam
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Gets as fault
     *
     * @return OperationFault
     */
    public function getFault()
    {
        return $this->fault;
    }
}
