<?php
namespace Samdevbr\Bigreport\Concerns;

interface Writer
{
    function loadConfig();
    function write(array $row);
    function close();
    function writeHeaders(array $headers);
    static function make(string $filename);
}
