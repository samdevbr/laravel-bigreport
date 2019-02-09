<?php
namespace Samdevbr\Bigreport\Concerns;

interface Writer
{
    public function setHeading(array $headings);
    public function addRow(array $row);
    public function addRows(array $rows);
}
