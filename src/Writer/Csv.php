<?php
namespace Samdevbr\Bigreport\Writer;
class Csv extends BaseWriter
{
    private $delimiter;
    private $enclosure;
    private $lineEnding;

    public function loadConfig()
    {
        $this->delimiter = config('bigreport.csv.delimiter');
        $this->enclosure = config('bigreport.csv.enclosure');
        $this->lineEnding = config('bigreport.csv.line_ending');
    }

    public function close()
    {
        if (!is_null($this->resource)) {
            fclose($this->resource);
        }
    }

    private function parseRow(array $row)
    {
        $values = [];
        
        foreach ($row as $value) {
            $values[] = $this->enclosure.$value.$this->enclosure;
        }

        $value = implode($this->delimiter, $values).$this->lineEnding;
        
        return iconv('ISO-8859-1', 'UTF-8', $value);
    }

    public function write(array $row)
    {
        if (is_null($this->resource)) {
            $this->resource = fopen(storage_path($this->filename), 'w+');
        }

        fwrite($this->resource, $this->parseRow($row));
    }

    public function writeHeaders(array $headers)
    {
        $this->write($headers);
    }
}
