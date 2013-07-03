<?php

namespace Squirrel\Debugger;

/**
 * Error file representation with a few lines shown around error line.
 *
 * @package Squirrel\Debugger
 * @author Valérian Galliat
 */
class DebugFile
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var integer
     */
    protected $line;

    /**
     * @var array
     */
    protected $surroundingLines;

    /**
     * @throws \InvalidArgumentException If the file does not exists.
     * @param string $file Exception file.
     * @param integer $line Exception line.
     */
    public function __construct($file, $line)
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException('Given file does not exists.');
        }

        $this->file = realpath($file);
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Get file lines surrounding error line.
     *
     * @param integer $count Count of lines to show (should be odd to center line).
     * @return array
     */
    public function getSurroundingLines($count = 11)
    {
        if (isset($this->surroundingLines)) {
            return $this->surroundingLines;
        }

        $lines = file($this->file, FILE_IGNORE_NEW_LINES);
        $lines[] = '';
        $this->surroundingLines = array();
        $offset = $this->line - (int) ceil($count / 2);

        if ($offset < 0) {
            $count += $offset;
            $offset  = 0;
        }

        for ($i = $offset, $end = $offset + $count; $i < $end; $i++) {
            if (isset($lines[$i])) {
                $this->surroundingLines[] = array($i + 1, $lines[$i]);
            }
        }

        return $this->surroundingLines;
    }
}
