<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Database\Instance;
use App\Database\Support\InstanceSetup;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RemoveInstance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lmt:remove {name} {--restore} {--forceDelete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a specified Instance.';

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
        if ($this->option('forceDelete')) {
            if (! $this->confirm('This will delete the instance and its database.'."\n\n".' Are you sure you wish to continue?')) {
                return;
            }
        }

        try {
            $instance = Instance::where('name', $this->argument('name'))->orWhere('slug', $this->argument('name'))->withTrashed()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $this->error("\n\n".'The “'.$this->argument('name').'” Instance does not exist.'."\n");
            return false;
        }

        if ($this->option('restore')) {
            $migration = InstanceSetup::restore($instance);
            $this->info($migration->output);

            return;
        }

        if ($this->option('forceDelete')) {
            $migration = InstanceSetup::forceDelete($instance);
            $this->info($migration->output);
            return;
        }

        $migration = InstanceSetup::softDelete($instance);
        $this->info($migration->output);
    }
}
