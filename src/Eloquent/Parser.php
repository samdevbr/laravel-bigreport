<?php
namespace Samdevbr\Bigreport\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Samdevbr\Bigreport\Fields\FieldCollection;
use Illuminate\Support\Str;

class Parser
{
    /**
     * @var Builder $builder
     */
    private $builder;

    /**
     * @var FieldCollection $fieldCollection
     */
    private $fieldCollection;

    public function __construct(Builder $builder, FieldCollection $fieldCollection)
    {
        $this->builder = &$builder;
        $this->fieldCollection = $fieldCollection;
    }

    private function parseColumns($columns)
    {
        $this->builder->setQuery(
            $this->builder->getQuery()->select($columns)
        );
    }

    private function parseRelations()
    {
        $relationFields = $this->fieldCollection->getRelations();

        $relations = [];

        foreach ($relationFields as $relationField) {
            if (!isset($relations[$relationField->method])) {
                $relations[$relationField->method] = [];
            }

            $relations[$relationField->method][] = $relationField->attribute;
        }

        $columns = $this->fieldCollection->getAttributes();

        foreach ($relations as $method => $attributes) {
            $relationString = $method;

            $relationModel = $this->builder->getRelation($method);
            $primaryKey = $relationModel->getRelated()->getKeyName();
            $hasPrimaryKeyInAttributes = in_array($primaryKey, $attributes);

            if (!$hasPrimaryKeyInAttributes) {
                $relationString .= ':'.$primaryKey.',';
            }

            $relationString .= implode(',', $attributes);

            $this->builder->with($relationString);
            
            $columns = array_unique(array_merge($columns, [$primaryKey]));
        }

        return array_flatten($columns);
    }

    public function parseAttributes()
    {
        $columns = $this->fieldCollection->getAttributes();

        if ($this->fieldCollection->hasRelations()) {
            $columns = $this->parseRelations();
        }

        $this->parseColumns($columns);
    }

    public static function make(Builder &$builder, FieldCollection $fieldCollection)
    {
        return new static($builder, $fieldCollection);
    }
}
