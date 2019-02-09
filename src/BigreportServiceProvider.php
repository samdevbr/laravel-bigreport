<?php
namespace Samdevbr\Bigreport;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Samdevbr\Bigreport\Fields\FieldCollection;

class BigreportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__.'/../config/bigreport.php' => config_path('bigreport.php')
                ],
                'config'
            );
        }

        $this->mergeConfigFrom(__DIR__.'/../config/bigreport.php', 'bigreport');

        Builder::macro('export', function (FieldCollection $fieldCollection, int $chunkSize = 1000) {
            return Export::make($this, $fieldCollection, $chunkSize);
        });
    }
}
