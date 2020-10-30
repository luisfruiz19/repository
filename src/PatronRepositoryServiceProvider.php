<?php


namespace PatronRepository\Repository;

use Illuminate\Support\ServiceProvider;

class PatronRepositoryServiceProvider extends ServiceProvider
{

    public function register()
    { }

    public function boot()
    {
        //publicar Generator
        $this->publishes([
            __DIR__ . '/../Console' =>  app_path('Console')
        ], 'PatronRepository-generator');
        //publicar Core
        $this->publishes([
            __DIR__ . '/../Core' =>  app_path('Core')
        ], 'PatronRepository-core');
        //publicar Repositories
        $this->publishes([
            __DIR__ . '/../Repositories' =>  app_path('Repositories')
        ], 'PatronRepository-repositories');
    }
}
