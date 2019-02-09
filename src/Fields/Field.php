<?php
namespace Samdevbr\Bigreport\Fields;

use Illuminate\Support\Str;


abstract class Field
{
    /**
     * @var string $name 
     */  
    public $name;

    /**
     * @var string $attribute
     */
    public $attribute;

    /**
     * @var string $value
     */ 
    public $value;

    /**
     * @var bool $isRelation
     */
    public $isRelation = false;

    /**
     * @var \Closure $resolver
     */
    protected $resolver;

    public function __construct(string $name, string $attribute, \Closure $resolver = null)
    {
        $this->name = @iconv('ISO-8859-1', 'UTF-8', $name);
        $this->attribute = $attribute;
        $this->resolver = $resolver;

        $this->parse();
    }

    public function getValue($value)
    {
        if (!is_null($this->resolver)) {
            $resolver = $this->resolver;

            $value = $resolver($value);
        }

        return @iconv('ISO-8859-1', 'UTF-8', $value);
    }

    protected function parse()
    {
        //
    }

    /**
     * Create a new filed instance
     * 
     * @param string $name
     * @param string $attribute
     * @param \Closure $resolver
     * @return static
     */
    public static function make(string $name, string $attribute, \Closure $resolver = null)
    {
        return new static($name, $attribute, $resolver);
    }
}
