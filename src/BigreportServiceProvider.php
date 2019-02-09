<?php
namespace Samdevbr\Bigreport;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

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

        Builder::macro('export', function (string $filename, array $headings, int $chunkSize = 1000) {
            $bigReport = new Bigreport($this, $filename, $headings, $chunkSize);

            return $bigReport->export();
        });
    }
}
