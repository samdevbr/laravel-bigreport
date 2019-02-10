# Easily create reports with massive amount of data directly from your Eloquent Models

This package allows you to quickly export Eloquent models into CSV files.

## Installation

* You can install this package using composer
`composer require samdevbr/laravel-bigreport`
* Publish configuration files
`php artisan vendor:publish --tag=congig`
* Register the service provider
```php
// config/app.php

// ...

Illuminate\View\ViewServiceProvider::class,

/*
 * Package Service Providers...
 */
Samdevbr\Bigreport\BigreportServiceProvider::class,

// ...
``` 

## Benchmarking

### This package has been tested with the following conditions

**Table size**: 20.5 MB

**Rows**: 80K

**Relations**: 2 Relations

**Columns**: 18

**Seconds to export**: 4 seconds

## Basic Usage

* As this package uses Eloquent models you must have them prepared, or just create one with us.

`php artisan make:model Post`
```php

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'post';

    public function author()
    {
        return $this->belongsTo(User::class);
    }
}
```

**If you want to test this package with it's full capacity you must populate your table with at least 80k entries. (Just use seeds)**

* Now you must create a field collection before exporting your data
  * Field Collection tells the package what columns should be exported
  
```php
use Samdevbr\Bigreport\Fields\FieldCollection;
use Samdevbr\Bigreport\Fields\BelongsTo;
use Samdevbr\Bigreport\Fields\Text;

$fields = [
  Text::make('Post ID', 'id'),
  Text::make('Title', 'title'),
  Text::make('Description', 'description'),
  BelongsTo::make('Author', 'author.name'),
];

$fieldCollection = FieldCollection::make($fields);
```

**Note that we used two types of fields `Text` and `BelongsTo`, the difference is that `BelongsTo` will map the informed relation within the model.**

* Optionally you can create value resolvers for your field.
  * When a resolver is informed the package will use the function to format the original value and return it.
```php
use Samdevbr\Bigreport\Fields\FieldCollection;
use Samdevbr\Bigreport\Fields\BelongsTo;
use Samdevbr\Bigreport\Fields\Text;

$fields = [
  Text::make('Post ID', 'id'),
  Text::make('Title', 'title'),
  Text::make('Description', 'description', function ($description) {
    return str_limit($description, 20);
  }),
  BelongsTo::make('Author', 'author.name'),
];

$fieldCollection = FieldCollection::make($fields);
```
### Now you just has to export the data from your eloquent model
```php
use App\Post;

$export = Post::export($fieldCollection);

return $export->download('filename.csv');
```
