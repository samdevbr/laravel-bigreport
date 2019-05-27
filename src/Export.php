<?php
namespace Samdevbr\Bigreport;

use Illuminate\Database\Eloquent\Builder;
use Samdevbr\Bigreport\Fields\FieldCollection;
use Samdevbr\Bigreport\Writer\BaseWriter as Writer;
use Illuminate\Routing\ResponseFactory;
use Samdevbr\Bigreport\Eloquent\Parser;
use Samdevbr\Bigreport\Concerns\ShouldExport;
use Samdevbr\Bigreport\Concerns\HasAdditionalHeadings;
use Samdevbr\Bigreport\Concerns\InteractsWithHeader;
use Samdevbr\Bigreport\Concerns\InteractsWithRows;

class Export
{
    /**
     * @var Builder $builder
     */
    private $builder;

    /**
     * @var ShouldExport $exportHandler
     */
    private $exportHandler;

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
     * @param ShouldExport $exportHandler
     * @param int $chunkSize
     * @return void
     */
    public function __construct(Builder $builder, ShouldExport $exportHandler, int $chunkSize = 1000)
    {
        $this->builder = $builder;
        $this->exportHandler = $exportHandler;

        $this->fieldCollection = FieldCollection::make($this->exportHandler->fields());

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

    private function headers()
    {
        return $this->fieldCollection->getHeaders();
    }

    private function hasAdditionalHeadings()
    {
        if (!$this->exportHandler instanceof InteractsWithHeader) {
            return false;
        }

        if (empty($this->exportHandler->handleHeader($this->headers()))) {
            return false;
        }

        return true;
    }

    /**
     * Generate the report
     *
     * @return void
     */
    public function generate()
    {
        $this->generateHeaders();

        Parser::make(
            $this->builder,
            $this->fieldCollection
        )->parseAttributes();

        $this->builder->chunk($this->chunkSize, function ($models) {
            foreach ($models as $model) {
                $row = $this->fieldCollection->modelToRow($model);

                if ($this->exportHandler instanceof InteractsWithRows) {
                    $row = $this->exportHandler->onEachRow($row);
                }

                $this->writer->write(
                    $row
                );
            }
        });

        $this->writer->close();
    }

    private function generateHeaders()
    {
        $headers = $this->headers();

        if ($this->hasAdditionalHeadings()) {
            $headers = $this->exportHandler->handleHeader($this->headers());
        }

        $this->writer->writeHeaders(
            $headers
        );
    }

    public function getDownloadLink()
    {
        return storage_path($this->filename);
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
     * @param ShouldExport $exportHandler
     * @param int $chunkSize
     * @return static
     */
    public static function make(Builder $builder, ShouldExport $exportHandler, int $chunkSize = 1000)
    {
        return new static($builder, $exportHandler, $chunkSize);
    }
}
