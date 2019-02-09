<?php
namespace Samdevbr\Bigreport\Writer;

use Samdevbr\Bigreport\Concerns\Writer;
abstract class BaseWriter implements Writer
{
    /**
     * File handle that will hold the report
     * file
     *
     * @var resource $fileHandle
     */
    protected $fileHandle;

    /**
     * Filename for the report
     * 
     * @var string $filename
     */  
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
