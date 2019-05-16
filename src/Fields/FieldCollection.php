<?php
namespace Samdevbr\Bigreport\Fields;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FieldCollection extends Collection
{
    /**
     * Validate field collection
     * 
     * @throws \Exception
     * @return void
     */
    protected function validateFields()
    {
        $this->each(function ($field) {
            if (!$field instanceof Field) {
                throw new \Exception('Field collection contains invalid field.');
            }
        });
    }

    /**
     * Return the header of the collection
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->pluck('name')->toArray();
    }

    /**
     * Return all attributes of the collection
     * 
     * @return array
     */
    public function getAttributes()
    {
        return $this->where('isRelation', false)->pluck('attribute')->toArray();
    }

    public function getRelations()
    {
        return $this->where('isRelation', true);
    }

    public function hasRelations()
    {
        return $this->where('isRelation', true)->count() > 0;
    }

    public function modelToRow(Model $model)
    {
        $row = [];

        foreach ($this as $field) {
            $tempValue = $model->{$field->attribute};

            if ($field->isRelation) {
                $tempValue = $model->{$field->method}->{$field->attribute} ?? '';
            }

            $row[] = $field->getValue($tempValue);
        }

        return $row;
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param  array  $items
     * @return static
     */
    public static function make($items = [])
    {
        $fieldCollection = new static($items);

        $fieldCollection->validateFields();

        return $fieldCollection;
    }
}
