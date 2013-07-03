<?php

namespace Squirrel\Debugger;

/**
 * Represents a single stack trace element.
 *
 * @package Squirrel\Debugger
 * @author Valérian Galliat
 */
class StackTraceElement
{
    /**
     * @var array
     */
    protected $call;

    /**
     * @var ErrorFile
     */
    protected $file;

    /**
     * @throws \InvalidArgumentException If the call is not valid.
     * @param array $call Function call representation in stack trace.
     */
    public function __construct(array $call)
    {
        if (!isset($call['function'])) {
            throw new \InvalidArgumentException('The call must have a function.');
        }

        $this->call = $call;
    }

    /**
     * @return boolean
     */
    public function hasFile()
    {
        return isset($this->call['file'], $this->call['line']);
    }

    /**
     * @return ErrorFile|null
     */
    public function getFile()
    {
        if (isset($this->file)) {
            return $this->file;
        }

        if (!$this->hasFile()) {
            return null;
        }

        return $this->file = new DebugFile($this->call['file'], $this->call['line']);
    }

    /**
     * @return boolean
     */
    public function isObject()
    {
        return isset($this->call['class'], $this->call['type']);
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return isset($this->call['class']) ? $this->call['class'] : null;
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return isset($this->call['type']) ? $this->call['type'] : null;
    }

    /**
     * @return string|null
     */
    public function getMethod()
    {
        if (!$this->isObject()) {
            return null;
        }

        return $this->call['function'];
    }

    /**
     * @return string|null
     */
    public function getFunction()
    {
        if ($this->isObject()) {
            return null;
        }

        return isset($this->call['function']) ? $this->call['function'] : null;
    }

    /**
     * @return boolean
     */
    public function hasArguments()
    {
        return isset($this->call['args']) && !empty($this->call['args']);
    }

    /**
     * @return array|null
     */
    public function getArguments()
    {
        if (!$this->hasArguments()) {
            return null;
        }

        return $this->call['args'];
    }
}
