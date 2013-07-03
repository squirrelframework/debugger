<?php

namespace Squirrel\Debugger;

/**
 * Basic class to handle PHP errors
 * and exceptions in specific output.
 *
 * Can be extended for specific handling.
 *
 * @package Squirrel\Debugger
 * @author Valérian Galliat
 */
class Debugger
{
    /**
     * @var array List of PHP errors.
     */
    public static $errors = array(
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ALL => 'E_ALL'
    );

    /**
     * @var boolean Is debug mode.
     */
    protected $debug;

    /**
     * @param boolean $debug Optional debug mode, true by default.
     */
    public function __construct($debug = true)
    {
        $this->debug = $debug;
    }

    /**
     * Registers this instance to PHP error events.
     */
    public function register()
    {
        error_reporting(E_ALL);
        set_error_handler(array($this, 'error'));
        set_exception_handler(array($this, 'exception'));
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Main handler for all exceptions and errors.
     *
     * @param \Exception
     * @return void
     */
    public function exception(\Exception $exception)
    {
        if ($this->debug) {
            echo $exception;
        } else {
            echo 'A fatal error has occurred.';
        }

        exit;
    }

    /**
     * Handler for PHP error event, converts error in exception
     * and let it propagate so other scripts can catch
     * errors in a try catch block.
     *
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public function error($code, $message, $file, $line)
    {
        throw new \ErrorException($message, $code, 0, $file, $line);
    }

    /**
     * Handler for PHP shotdown event,
     * handle last error if exists.
     *
     * @return void
     */
    public function shutdown()
    {
        if (($error = error_get_last()) !== null) {
            $this->exception(new \ErrorException(
                $error['message'],
                $error['type'],
                0,
                $error['file'],
                $error['line']
            ));
        }
    }
}
