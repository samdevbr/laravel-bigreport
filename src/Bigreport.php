<?php
namespace Samdevbr\Bigreport;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Samdevbr\Bigreport\Writer\Writer;
use Illuminate\Support\Str;
use Illuminate\Routing\ResponseFactory;

class Bigreport
{
    /**
     * Eloquent Builder with applied conditions
     *
     * @var EloquentBuilder $eloquentBuilder
     */
    private $eloquentBuilder;

    /**
     * Query Builder from eloquent model
     *
     * @var QueryBuilder $queryBuilder
     */
    private $queryBuilder;

    /**
     * Filename of the report
     *
     * @var string $filename
     */
    private $filename;

    /**
     * Array with column and corresponding header text relations
     *
     * @var array $headings
     */
    private $headings;

    /**
     * Chunk size
     *
     * @var int $chunkSize
     */
    private $chunkSize;

    /**
     * Array with available writers
     *
     * @var array $writers
     */
    private $writers;

    /**
     * Selected writer by the extension filename
     *
     * @var Writer $writer
     */
    private $writer;
    
    /**
     * Parsed columns from headings
     * 
     * @var array $columns
     */
    private $columns = [];

    /**
     * Parsed relations from headings
     * 
     * @var array $relations
     */
    private $relations = [];

    /**
     * @param EloquentBuilder $eloquentBuilder Eloquent Builder with applied conditions
     * @param string $filename Filename of the report
     * @param array $headings Array with column and corresponding header text relations
     * @param int $chunkSize Chunk size
     */
    public function __construct(Builder $eloquentBuilder, string $filename = '', array $headings = [], int $chunkSize = 1000)
    {
        $this->eloquentBuilder = $eloquentBuilder;
        $this->queryBuilder = $this->eloquentBuilder->getQuery();
        $this->filename = empty($filename) ? time().'.csv' : $filename;
        $this->headings = $headings;
        $this->chunkSize = $chunkSize;

        $this->writers = config('bigreport.extension_mapping');

        $this->validateFilename();
    }

    private function validateFilename()
    {
        $filenameParts = explode('.', $this->filename);

        $extension = last($filenameParts);

        if (is_null($extension)) {
            throw new \Exception('Invalid filename, a filename must have an valid extension.');
        }

        // Normalize extension
        $extension = strtolower($extension);

        if (!isset($this->writers[$extension])) {
            throw new \Exception("Bigexport library does not support .{$extension} yet.");
        }

        $writerClass = $this->writers[$extension];
        $writer = app()->make($writerClass);

        if (!$writer instanceof Writer) {
            throw new \Exception("{$writerClass} isn't a instance of \Bigreport\Concerns\Writer");
        }

        $this->writer = $writer;

        if ($this->writer->requiresFilename) {
            $this->writer->filename = $this->filename;
        }
    }

    private function hasHeadings()
    {
        return count($this->headings) > 0;
    }

    // Load eloquent relationships
    private function parseRelations()
    {
        $keys = array_keys($this->headings);

        $this->relations = array_filter($keys, function ($key) {
            return Str::contains($key, '.');
        });

        if (empty($this->relations)) {
            return;
        }

        $parsedRelations = [];

        foreach ($this->relations as $relation) {
            if (!isset($parsedRelations[$relation])) {
                $relationParts = explode('.', $relation);
                $relation = head($relationParts);

                $this->eloquentBuilder->with($relation);
            }

            $parsedRelations[] = $relation;
        }
    }

    private function hasRelations()
    {
        return count($this->relations) > 0;
    }

    /**
     * Override select clause to avoid
     * unnecessary columns in the
     * query
     *
     * @return array
     */
    private function parseColumns()
    {
        $keys = array_keys($this->headings);

        $columns = array_filter($keys, function ($key) {
            return !Str::contains($key, '.');
        });

        $this->columns = array_values($columns);

        $this->queryBuilder->select($columns);
        $this->eloquentBuilder->setQuery($this->queryBuilder);
    }

    private function getRelationsFromModel($model)
    {
        $relations = [];

        foreach ($this->relations as $relation) {
            $relationParts = explode('.', $relation);
            $relationMethod = $model->{head($relationParts)};
            $relationValue = $relationMethod;
            $relationTree = array_splice($relationParts, 1, count($relationParts));

            foreach ($relationTree as $item) {
                $relationValue = $relationValue->{$item};
            }

            $relations[$relation] = $relationValue;
        }

        return $relations;
    }

    public function export()
    {
        if ($this->hasHeadings()) {
            $this->parseColumns();
            $this->parseRelations();

            $this->writer->setHeading($this->columns);
        }

        $attributes = array_keys($this->headings);

        $this->eloquentBuilder->chunk($this->chunkSize, function ($models) {
            $rows = [];

            foreach ($models as $model) {
                $row = [];

                if ($this->hasRelations()) {
                    $relations = $this->getRelationsFromModel($model);
                }

                foreach ($attributes as $attribute) {
                    $row[] = isset($relations[$attribute]) ? $relations[$attribute] : $model->{$attribute};
                }

                $rows[] = $row;
            }

            $this->writer->addRows($rows);
        });

        return $this;
    }

    public function download()
    {
        return ResponseFactory::download(
            $this->writer->filename,
            $this->filename
        )->deleteFileAfterSend();
    }

    public function path()
    {
        return $this->writer->filename;
    }
}
