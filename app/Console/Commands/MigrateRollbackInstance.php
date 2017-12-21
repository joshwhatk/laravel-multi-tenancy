<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Database\Instance;
use App\Database\Support\InstanceSetup;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MigrateRollbackInstance extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
    protected $signature = 'lmt:migrate:rollback {name} {--test}';

  /**
   * The console command description.
   *
   * @var string
   */
    protected $description = 'Rollback migrations on a specified Instance.';

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
        try {
            $instance = Instance::where('name', $this->argument('name'))->orWhere('slug', $this->argument('name'))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $this->error("\n\n".'The “'.$this->argument('name').'” Instance does not exist.'."\n");
            return false;
        }

        if (! $this->option('test')) {
            $migration = InstanceSetup::migrate($instance, 'rollback');
            $this->info($migration->output);
        }
    }
}
