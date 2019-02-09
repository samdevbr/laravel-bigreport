# Laravel BigReport
Package to generate reports with massive amount of data in Laravel.

# Benchmarking

Currently this package only supports exporting CSV file formats, and has been tested exporting 80k entries with relationships taking around 7 seconds to do so.

# Installation

* Install the package via composer
`composer require samdevbr/laravel-bigreport`

* Publish configuration file
`php artisan vendor:publish --tag="config"`

* Register the service provider in `config/app.php`
```php
Illuminate\View\ViewServiceProvider::class,

/*
 * Package Service Providers...
 */
Samdevbr\Bigreport\BigreportServiceProvider::class,
```
# Basic Usage

This package works throught your Eloquent Models, so first create a model if needed
`php artisan make:model Author`

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $table = 'author';
}

```
Then you can easily export data from the model
```php
<?php

use App\Author;

$export = Author::export('filename.csv', [
  'id' => 'ID',
  'name' => 'Name',
  'created_at' => 'Creation Time'
]);

```

You can also download the generated report
```php
$export->download();
```

You can also get the path to the generated report file
```php
$export->path();
```

# Working with Relationships
Let's say that our author `hasMany` posts and we want to get all Posts with the Author.

Create the model if needed
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'post';
    
    public function author()
    {
      return $this->belongsTo(Author::class);
    }
}

```
Then again you can easily export the posts with author informations
```php

$export = Post::export('posts.csv', [
  'title' => 'Title',
  'author.name' => 'Author Name'
]);

$export->download();
```
