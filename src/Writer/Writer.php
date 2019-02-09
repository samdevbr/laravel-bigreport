<?php
namespace Samdevbr\Bigreport\Writer;

use Samdevbr\Bigreport\Concerns\Writer;

abstract class Writer implements Writer
{
    public $requiresFilename = false;
    public $filename;

    protected $fileHandle;

    public function __construct()
    {
        if ($this->requiresFilename) {
            $this->filename = storage_path($this->filename);

            $this->fileHandle = fopen($this->filename, 'w+');
        }
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

    public function __destruct()
    {
        fclose($this->fileHandle);
    }
}
