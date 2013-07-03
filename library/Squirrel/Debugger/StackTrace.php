<?php

namespace Squirrel\Debugger;

/**
 * Stack trace object representation for exceptions handling.
 *
 * @package Squirrel\Debugger
 * @author Valérian Galliat
 */
class StackTrace implements \Iterator
{
    /**
     * @var array
     */
    protected $stackTrace;

    /**
     * @var integer
     */
    protected $position;

    /**
     * @param array $stackTrace PHP exception stack trace.
     */
    public function __construct(array $stackTrace)
    {
        $this->stackTrace = $stackTrace;
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        if ($this->stackTrace[$this->position] instanceof StackTraceElement) {
            return $this->stackTrace[$this->position];
        }

        return $this->stackTrace[$this->position] = new StackTraceElement($this->stackTrace[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->stackTrace[$this->position]);
    }
}
