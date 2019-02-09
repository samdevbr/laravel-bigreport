<?php
namespace Samdevbr\Bigreport\Fields;

class BelongsTo extends Field
{
    public $isRelation = true;

    protected function parse()
    {
        $relationParts = explode('.', $this->attribute);

        $this->method = head($relationParts);
        $this->attribute = last($relationParts);        
    }
}
