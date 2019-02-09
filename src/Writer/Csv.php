<?php
namespace Samdevbr\Bigreport\Writer;

class Csv extends BaseWriter
{
    private $delimiter;
    private $enclosure;
    private $lineEnding;

    public function __construct()
    {
        $this->delimiter = config('bigreport.csv.delimiter');
        $this->enclosure = config('bigreport.csv.enclosure');
        $this->lineEnding = config('bigreport.csv.line_ending');
    }

    public function setHeading(array $headings)
    {
        $this->addRow($headings);
    }

    public function addRow(array $row)
    {
        $rawRow = '';
        $lastValue = last($row);

        foreach ($row as $value) {
            $rawRow .= $this->enclosure;
            $rawRow .= @iconv('ISO-8859-1', 'UTF-8', $value);
            $rawRow .= $this->enclosure;

            if ($value !== $lastValue) {
                $rawRow .= $this->delimiter;
            }
        }

        $rawRow .= $this->lineEnding;

        fwrite($this->fileHandle, $rawRow);
    }

    public function addRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
    }
}
