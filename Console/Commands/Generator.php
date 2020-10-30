<?php

namespace App\Console\Commands;

use App\Core\Remplace\Remplacer;
use Illuminate\Console\Command;

class Generator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generator {type} {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        if ($this->argument('type') == 'repository') {
            $this->createRepository($this->argument('model'));
        }
        if ($this->argument('type') == 'controller') {
            $this->createApiController($this->argument('model'));
        }
    }

    public function createRepository($modelName)
    {
        $remplacer = new Remplacer(base_path() . "/app/Core/Remplace/ModelRepositoryTemplate.stub");
        $remplacer->remplace('MODEL', $modelName);
        $result = $remplacer->save(base_path() . "/app/Repositories/{$modelName}Repository.php");
        $this->output->writeln($result);
    }

    public function createApiController($modelName)
    {
        $remplacer = new Remplacer(base_path() . "/app/Core/Remplace/ModelAPIControllerTemplate.stub");
        $remplacer->remplace('MODEL', $modelName);
        $remplacer->remplace('MIN_MODEL', strtolower(substr($modelName, 0, 1)) . substr($modelName, 1, strlen($modelName) - 1));
        $result = $remplacer->save(base_path() . "/app/Http/Controllers/API/{$modelName}APIController.php");
        $this->output->writeln($result);
    }
}
