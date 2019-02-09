<?php
namespace Samdevbr\Bigreport\Writer;

use Samdevbr\Bigreport\Concerns\Writer;
abstract class BaseWriter implements Writer
{
    protected $fileHandle;
    public $filename;

    public function openHandle()
    {
        $this->fileHandle = fopen(storage_path($this->filename), 'w+');
    }

    public function closeHandle()
    {
        fclose($this->fileHandle);
    }

    public function setHeading(array $headings)
    {
        //
    }

    public function addRow(array $row)
    {
        //
    }

    public function addRows(array $rows)
    {
        //
    }
}
