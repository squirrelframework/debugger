<?php

namespace Squirrel\Debugger;

/**
 * Singleton class to handle some PHP
 * error events, providing a debug error page.
 *
 * @package Squirrel\Debugger
 * @author Valérian Galliat
 */
class HttpDebugger extends Debugger
{
    /**
     * @var boolean Has already already shown debug page.
     */
    protected $debugged;

    /**
     * @param boolean $debug Optional debug mode, true by default.
     */
    public function __construct($debug = true)
    {
        parent::__construct($debug);
        $this->debugged = false;
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        ob_start();
        parent::register();
    }

    /**
     * {@inheritdoc}
     */
    public function exception(\Exception $exception)
    {
        $this->clean();

        if (!$this->debug || $this->debugged) {
            $this->sendHeaders(true);
            parent::exception($exception);
            exit;
        }

        $this->debugged = true;

        try {
            $this->debug($exception);
        } catch (\Exception $exception) {
            $this->clean();
            $this->sendHeaders(true);
            parent::exception($exception);
        }

        exit;
    }

    /**
     * Shows core debug page with stack trace.
     *
     * @param \Exception
     */
    protected function debug(\Exception $exception)
    {
        $this->sendHeaders();

        $type = get_class($exception);
        $code = $exception->getCode();
        $message = $exception->getMessage();

        if ($exception instanceof ErrorException && isset(self::$errors[$code])) {
            $code = self::$errors[$code];
        }

        $title = $type . ' [ ' . $code . ' ]: ' . $message;

        echo $this->getLayout(
            $title,
            $this->getContent($title, $exception),
            $this->getStyle(),
            $this->getScript()
        );
    }

    /**
     * Cleans previously opened output buffers
     * and starts a clean buffer.
     */
    protected function clean()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();
    }

    /**
     * Sends HTTP headers for an internal error.
     *
     * @param boolean $text Whether the response is plain text.
     */
    protected function sendHeaders($text = false)
    {
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header(sprintf('%s %s %s', $protocol, 500, 'Internal Server Error'));

        if ($text) {
            header('Content-Type: text/plain');
        }
    }

    /**
     * Gets HTML content to display given exception.
     * 
     * @param string $title
     * @param \Exception $exception
     * @return string
     */
    protected function getContent($title, \Exception $exception)
    {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $lines = $this->getContentFileLines(new DebugFile($exception->getFile(), $exception->getLine()));
        $trace = $this->getContentStackTrace(new StackTrace($exception->getTrace()));

        return <<<EOF
<header id="header">
<h1>$title</h1>
</header>
<section id="section">
<p>$file [ $line ]</p>
$lines
$trace
</section>
EOF;
    }

    /**
     * Gets HTML for each item of given stack trace.
     *
     * @param StackTrace $stackTrace
     */
    protected function getContentStackTrace(StackTrace $stackTrace)
    {
        $content = '<ol id="calls">';

        foreach ($stackTrace as $element) {
            $content .= '<li><p>';
            
            if ($element->hasFile()) {
                $file = $element->getFile();
                $content .= '<a class="rows" href="#">' . $file->getFile() . ' [ ' . $file->getLine() . ' ]</a>';
            } else {
                $content .= 'PHP internal call';
            }

            $content .= ': ';

            if ($element->isObject()) {
                $content .= $element->getClass() . $element->getOperator() . $element->getMethod();
            } else {
                $content .= $element->getFunction();
            }

            $content .= '(';

            if ($element->hasArguments()) {
                $content .= '<a class="arguments" href="#">arguments</a>';
            }

            $content .= ')</p>';

            if ($element->hasFile()) {
                $content .= $this->getContentFileLines($element->getFile());
            }

            if ($element->hasArguments()) {
                $content .= '<ol class="arguments">';

                foreach ($element->getArguments() as $argument) {
                    $content .= '<li><pre>' . $this->getContentArgument($argument) . '</pre></li>';
                }

                $content .= '</ol>';
            }

            $content .= '</li>';
        }

        $content .= '</ol>';
        return $content;
    }

    /**
     * Gets given file lines as HTML.
     * 
     * @param DebugFile $file
     * @return string
     */
    protected function getContentFileLines(DebugFile $file)
    {
        $content = '<pre class="rows"><ol class="lines">';

        foreach ($file->getSurroundingLines() as $line) {
            $content .= '<li' . ($line[0] === $file->getLine() ? ' class="current"' : '') . '>' .  $line[0] . '</li>';
        }

        $content .= '</ol><ol class="contents">';

        foreach ($file->getSurroundingLines() as $line) {
            $content .= '<li' . ($line[0] === $file->getLine() ? ' class="current"' : '') . '>';

            if (!empty($line[1])) {
                $content .= htmlspecialchars($line[1]);
            } else {
                $content .= '&nbsp;';
            }

            $content .= '</li>';
        }

        $content .= '</ol></pre>';
        return $content;
    }

    /**
     * Gets HTML output for debug given argument.
     * 
     * @param mixed $argument
     * @return string
     */
    protected function getContentArgument($argument)
    {
        return var_export($argument, true);
    }

    /**
     * Gets CSS style for exception page.
     * 
     * @return string
     */
    protected function getStyle()
    {
        return <<<EOF
* {
    margin: 0px;
    padding: 0px;
    border: none;
    background-color: transparent;
    color: inherit;
    vertical-align: baseline;
    text-align: inherit;
    text-decoration: inherit;
    white-space: inherit;
    font-weight: inherit;
    font-style: inherit;
    font-size: inherit;
    font-family: inherit;
    line-height: inherit;
}

ul, ol {
    list-style: none;
}

table {
    border-collapse: collapse;
}

input, textarea {
    outline: none;
}

html {
    width: 100%;
    height: 100%;
    color: black;
    text-align: left;
    text-decoration: none;
    white-space: normal;
    font-weight: normal;
    font-style: normal;
    font-size: 16px;
    font-family: "Verdana", "Geneva", "sans-serif";
    line-height: 24px;
}

body {
    background: hsl(0, 0%, 95%);
    color: hsl(0, 0%, 35%);
    height: 100%;
    width: 100%;
}

::selection {
    color: hsl(25, 100%, 50%);
}

header#header {
    background: hsl(0, 0%, 35%);
    color: hsl(0, 0%, 95%);
    padding: 32px;
}

section#section {
    padding: 32px;
}

section#section > pre {
    margin-top: 32px;
}

ol#calls > li {
    margin-top: 32px;
}

ol#calls > li pre.rows {
    margin-top: 32px;
}

ol#calls > li ol.arguments > li {
    margin-top: 32px;
}

pre {
    background: hsl(0, 0%, 35%);
    color: hsl(0, 0%, 95%);
    font-family: "Consolas", "Courier New", "monospace";
    font-size: 12px;
    line-height: 18px;
    overflow: hidden;
    padding: 12px;
    white-space: pre;
}

pre.rows {
    white-space: inherit;
}

pre.rows > ol {
    overflow: hidden;
    padding: 0px 8px;
}

pre.rows > ol.lines {
    float: left;
}

pre.rows > ol.contents > li {
    white-space: pre;
}

a {
    color: hsl(25, 100%, 50%);
}

EOF;
    }

    /**
     * Gets script for exception page.
     * 
     * @return string
     */
    protected function getScript()
    {
        return <<<EOF
(function () {
    'use strict';

    var process = function (call) {
        var rowsLink = call.querySelector('a.rows');
        var argumentsLink = call.querySelector('a.arguments');
        var rowsElement = call.querySelector('pre.rows');
        var argumentsElement = call.querySelector('ol.arguments');

        if (rowsLink && rowsElement) {
            rowsElement.style.display = 'none';

            rowsLink.addEventListener('click', function (event) {
                event.preventDefault();
                rowsElement.style.display = rowsElement.style.display === 'none' ? '' : 'none';
            });
        }

        if (argumentsLink && argumentsElement) {
            argumentsElement.style.display = 'none';

            argumentsLink.addEventListener('click', function (event) {
                event.preventDefault();
                argumentsElement.style.display = argumentsElement.style.display === 'none' ? '' : 'none';
            });
        }
    };

    var calls = document.querySelectorAll('ol#calls > li');
    var length = calls.length;

    for (var i = 0; i < length; i++) {
        process(calls[i]);
    }
})();

EOF;
    }

    /**
     * Gets global layout of exception page.
     *
     * @param string $title
     * @param string $content
     * @param string $style
     * @param string $script
     * @return string
     */
    protected function getLayout($title, $content, $style, $script)
    {
        return <<<EOF
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>$title</title>
<style>

$style

</style>
</head>
<body>
$content
<script>

$script

</script>
</body>
</html>

EOF;
    }
}
