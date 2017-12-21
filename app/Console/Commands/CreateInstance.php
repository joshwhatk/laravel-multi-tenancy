<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Database\Support\InstanceSetup;

class CreateInstance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lmt:create {name} {--slug=} {--test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Instance.';

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

        $slug = $this->option('slug');
        if (is_null($this->option('slug'))) {
            $slug = str_slug($this->argument('name'));
        }

        $this->info('Creating instance “'.$this->argument('name').'” - “'.$slug.'”.');
        if (! $this->option('test')) {
            $intanceCreated = InstanceSetup::create(['name' => $this->argument('name'), 'slug' => $slug]);
            $this->info($intanceCreated->output);
        }
        $this->info('“'.$this->argument('name').'” is now available at “'.config('app.url').'/'.$slug.'”.');
    }
}
