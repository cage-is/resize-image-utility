<?php
class Helper
{
    const GREEN = '1;33';
    const BLUE = '1;34';
    const RED = '1;31';

    /**
     * @var bool
     */
    private $isWindows;

    /**
     * Wraps a string in text that causes it to be colored when used on a linux
     * or unix shell.
     *
     * @param string $color
     * @param string $text
     * @return string
     */
    public function colorText($color, $text)
    {
        $prefix = !$this->isWindows() ? "\e[{$color}m" : '';
        $suffix = !$this->isWindows() ? "\e[0m" : '';
        return $prefix . $text . $suffix;
    }

    /**
     * Echos a string followed by a line break.
     *
     * @param $ln
     */
    public function println($ln)
    {
        echo $ln . PHP_EOL;
    }

    /**
     * Returns true if the OS is Windows.
     *
     * @return bool
     */
    public function isWindows()
    {
        if (is_null($this->isWindows)) {
            $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        }
        return $this->isWindows;
    }

    /**
     * Takes an array and a key and returns the value at that index if it
     * exists. Otherwise, it just returns the default value.
     *
     * @param array $array
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function arrayGet(array $array, $key, $default = '')
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
