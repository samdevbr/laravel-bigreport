<?php
namespace Samdevbr\Bigreport\Writer;

use Samdevbr\Bigreport\Concerns\Writer;
abstract class BaseWriter implements Writer
{
    /**
     * Constante with all supported report types
     */
    const TYPES = [
        'csv' => Csv::class
    ];

    /**
     * @var string $filename
     */
    protected $filename;

    /**
     * @var resource|null $resource
     */
    protected $resource = null;

    protected function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    public function loadConfig()
    {
        //
    }

    public function write(array $row)
    {
        //
    }

    public function close()
    {
        //
    }

    public function writeHeaders(array $headers)
    {
        //
    }

    private static function getExtension($filename)
    {
        return last(explode('.', $filename));
    }

    /**
     * Create a new writer instance
     *
     * @return Writer
     */
    public static function make(string $filename)
    {
        $extension = static::getExtension($filename);

        $writer = app(static::TYPES[$extension]);
        $writer->setFilename($filename);

        $writer->loadConfig();

        return $writer;
    }
}
