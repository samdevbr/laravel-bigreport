<?php
namespace Samdevbr\Bigreport;

use Illuminate\Database\Eloquent\Builder;
use Samdevbr\Bigreport\Fields\FieldCollection;
use Samdevbr\Bigreport\Writer\BaseWriter as Writer;
use Illuminate\Routing\ResponseFactory;
use Samdevbr\Bigreport\Eloquent\Parser;

class Export
{
    /**
     * @var Builder $builder
     */
    private $builder;

    /**
     * @var FieldCollection $fieldCollection
     */
    private $fieldCollection;

    /**
     * @var int $chunkSize
     */
    private $chunkSize;

    /**
     * @var string $filename
     */
    private $filename;

    /**
     * @var Writer $writer
     */
    private $writer;

    /**
     * Create a new instance of Export
     * 
     * @param Builder $builder
     * @param FieldCollection $fieldCollection
     * @param string $filename
     * @param int $chunkSize
     * @return void
     */
    public function __construct(Builder $builder, FieldCollection $fieldCollection, int $chunkSize = 1000)
    {
        $this->builder = $builder;
        $this->fieldCollection = $fieldCollection;
        $this->filename = time().'.csv';
        $this->chunkSize = $chunkSize;

        $this->createWriter();
    }

    /**
     * Create a new writer instance
     * based on file extension
     *
     * @return void
     */
    private function createWriter()
    {
        $this->writer = Writer::make($this->filename);
    }

    /**
     * Generate the report
     *
     * @return void
     */
    private function generate()
    {
        $this->writer->writeHeaders(
            $this->fieldCollection->getHeaders()
        );

        Parser::make(
            $this->builder,
            $this->fieldCollection
        )->parseAttributes();

        $this->builder->chunk($this->chunkSize, function ($models) {
            foreach ($models as $model) {
                $this->writer->write(
                    $this->fieldCollection->modelToRow($model)
                );
            }
        });

        $this->writer->close();
    }

    /**
     * Begin the creation of the report and
     * then download it.
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(string $name)
    {
        $this->generate();

        $responseFactory = app(ResponseFactory::class);

        return $responseFactory->download(
            storage_path($this->filename),
            $name
        )->deleteFileAfterSend();
    }

    /**
     * Create a new export instance
     *
     * @param Builder $builder
     * @param FieldCollection $fieldCollection
     * @param string $filename
     * @param int $chunkSize
     * @return static
     */
    public static function make(Builder $builder, FieldCollection $fieldCollection, int $chunkSize = 1000)
    {
        return new static($builder, $fieldCollection, $chunkSize);
    }
}
