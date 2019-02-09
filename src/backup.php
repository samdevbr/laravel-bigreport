<?php
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
    public function __construct(EloquentBuilder $eloquentBuilder, string $filename, array $headings, int $chunkSize = 1000)
    {
        $this->eloquentBuilder = $eloquentBuilder;
        $this->queryBuilder = $this->eloquentBuilder->getQuery();
        $this->filename = empty($filename) ? time() . '.csv' : $filename;
        $this->headings = $headings;
        $this->chunkSize = $chunkSize;

        $this->writers = config('bigreport.extension_mapping');

        $this->validateFilename();
    }

    /**
     * Validate given filename and create
     * correct writer for the given
     * file extension
     *
     * @return void
     */
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
            throw new \Exception("Bigreport library does not support .{$extension} yet.");
        }

        $writerClass = $this->writers[$extension];
        $writer = app()->make($writerClass);

        if (!$writer instanceof Writer) {
            throw new \Exception("{$writerClass} isn't a instance of \Bigreport\Concerns\Writer");
        }

        $this->writer = $writer;

        $this->writer->filename = $this->filename;
    }

    /**
     * Verify if export has headings
     *
     * @return bool
     */
    private function hasHeadings()
    {
        return count($this->headings) > 0;
    }

    /**
     * Parse main model columns by selecting
     * only necessary columns to avoid
     * uneeded columns in the query
     *
     * @return void
     */
    private function parseColumns()
    {
        $keys = array_keys($this->headings);

        $columns = array_filter($keys, function ($column) {
            return !Str::contains($column, '.');
        });

        $this->columns = $columns;
        $this->queryBuilder->select($this->columns);
        $this->eloquentBuilder->setQuery($this->queryBuilder);
    }

    /**
     * Call the relation method on the model
     * and return it
     *
     * @param string $relation
     * @return Relation
     */
    private function getRelation($relation)
    {
        return call_user_func([$this->eloquentBuilder->getModel(), $relation]);
    }

    /**
     * Parse relationships from model by selecting
     * only columns that is present in the
     * headings array to avoid uneeded
     * columns in the query
     * 
     * @return void
     */
    private function parseRelations()
    {
        $keys = array_keys($this->headings);

        $this->relations = array_filter($keys, function ($key) {
            return Str::contains($key, '.');
        });

        if (empty($this->relations)) {
            return;
        }

        $relationsToBeLoaded = [];

        foreach ($this->relations as $relation) {
            $relationParts = explode('.', $relation);
            $methodName = head($relationParts);
            $relationParts = array_splice($relationParts, 1, count($relationParts));

            if (!isset($relationsToBeLoaded[$methodName])) {
                $relationsToBeLoaded[$methodName] = [$relationParts];
                continue;
            }

            $relationsToBeLoaded[$methodName][] = $relationParts;
        }

        foreach ($relationsToBeLoaded as $method => $parts) {
            $relationColumns = array_flatten($parts);
            $relation = $this->getRelation($method);
            $relationKeyName = $relation->getRelated()->getKeyName();
            $relationColumns = implode(',', array_unique(
                array_merge($relationColumns, [$relationKeyName]),
                SORT_REGULAR
            ));

            $selectColumns = $this->queryBuilder->columns;
            $selectColumns[] = $relationKeyName;
            $this->queryBuilder->select($selectColumns);
            $this->eloquentBuilder->with(sprintf("%s:%s", $method, $relationColumns));
            $this->eloquentBuilder->setQuery($this->queryBuilder);
        }
    }

    /**
     * Verify if export has relations
     * 
     * @return bool
     */
    private function hasRelations()
    {
        return count($this->relations) > 0;
    }

    /**
     * Map eloquent relationships and return
     * array with key and value for the
     * relationship
     *
     * @return array
     */
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

    /**
     * Proccess the export for given Eloquent Model
     *
     * @return Export
     */
    public function export()
    {
        $this->writer->openHandle();

        if ($this->hasHeadings()) {
            $this->parseColumns();
            $this->parseRelations();

            $this->writer->setHeading(array_values($this->headings));
        }

        $attributes = array_keys($this->headings);

        $this->eloquentBuilder->chunk($this->chunkSize, function ($models) use ($attributes) {
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

        $this->writer->closeHandle();

        return $this;
    }

    /**
     * Download and delete the generated report
     * 
     * @return ResponseFactory
     */
    public function download()
    {
        $responseFactory = app()->make(ResponseFactory::class);

        return $responseFactory->download(
            storage_path($this->writer->filename),
            $this->filename
        )->deleteFileAfterSend();
    }

    /**
     * Return the filepath for the report
     * 
     * @return string
     */
    public function path()
    {
        return $this->writer->filename;
    }
