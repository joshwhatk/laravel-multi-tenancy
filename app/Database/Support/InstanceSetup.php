<?php

namespace App\Database\Support;

use Artisan;
use App\Support\Str;
use App\Database\Instance;
use Illuminate\Support\Facades\DB;

class InstanceSetup
{
    public $name;
    public $slug;
    public $database;
    public $output;
    protected $instance;
    protected $migrationsPath = 'database/migrations/instances';

    /**
     * Database prefix, not the tables within the database, but the actual database.
     *
     * @var string
     */
    public static $prefix = 'lmt_';

    public function __construct($name, $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->database = static::createDbName($this->name);
    }

    public static function create(array $params)
    {
        $instance = new static($params['name'], $params['slug']);
        $instance->run();

        return $instance;
    }

    public static function migrate(Instance $instance, $migrateAction = null)
    {
        return DB::transaction(function () use ($instance, $migrateAction) {
            $instance = new static($instance->name, $instance->slug);
            $instance->createConnection();
            $instance->runInstanceMigrations($migrateAction);

            return $instance;
        });
    }

    public static function softDelete(Instance $instance)
    {
        return DB::transaction(function () use ($instance) {
            $deleted_instance = new static($instance->name, $instance->slug);
            $instance->delete();
            $deleted_instance->output = 'Successfully deleted Instance “'.$deleted_instance->name.'”.';
            Instance::refreshInstances();
            return $deleted_instance;
        });
    }

    public static function restore(Instance $instance)
    {
        return DB::transaction(function () use ($instance) {
            $deleted_instance = new static($instance->name, $instance->slug);
            $instance->restore();
            $deleted_instance->output = 'Successfully restored Instance “'.$deleted_instance->name.'”.';
            Instance::refreshInstances();
            return $deleted_instance;
        });
    }

    public static function forceDelete(Instance $instance)
    {
        return DB::transaction(function () use ($instance) {
            $deleted_instance = new static($instance->name, $instance->slug);
            $instance->forceDelete();
            $deleted_instance->removeDatabase();

            Instance::refreshInstances();
            $deleted_instance->output = 'Successfully force deleted Instance “'.$deleted_instance->name.'” and database “'.$deleted_instance->database.'”.';

            return $deleted_instance;
        });
    }

    public static function createDbName($instance_name)
    {
        return static::$prefix.Str::db_name($instance_name);
    }

    private function run()
    {
        DB::transaction(function () {
            $this->saveInstance();
            $this->createDatabaseAndConnection();
            $this->runInstanceMigrations();
        });

        return $this->instance;
    }

    private function saveInstance()
    {
        $this->instance = Instance::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'database' => $this->database,
        ]);

        Instance::refreshInstances();
    }

    private function createDatabaseAndConnection()
    {
        DB::statement('CREATE DATABASE '.$this->database);

        $this->createConnection();
    }

    private function createConnection()
    {
        app('setDbConnectionAndCookieName', ['instance' => $this->instance]);
    }

    private function runInstanceMigrations($action = null)
    {
        if ($action == null) {
            return $this->migrateInstance();
        } elseif ($action == 'refresh') {
            return $this->refreshMigrations();
        } elseif ($action == 'rollback') {
            return $this->rollbackMigrations();
        }
    }

    private function migrateInstance()
    {
        return DB::transaction(function () {
            $artisan = Artisan::call('migrate', [
                '--database' => $this->slug,
                '--path' => $this->migrationsPath,
                '--force' => true,
            ]);

            $this->output = Artisan::output();
            return $artisan;
        });
    }

    private function refreshMigrations()
    {
        return DB::transaction(function () {
            $artisan = Artisan::call('migrate:reset', [
                '--database' => $this->slug,
            ]);
            $this->output = Artisan::output();

            $artisan = Artisan::call('migrate', [
                '--database' => $this->slug,
                '--path' => $this->migrationsPath,
            ]);

            $this->output .= Artisan::output();
            return $artisan;
        });
    }

    private function rollbackMigrations()
    {
        return DB::transaction(function () {
            $artisan = Artisan::call('migrate:rollback', [
                '--database' => $this->slug,
            ]);

            $this->output = Artisan::output();
            return $artisan;
        });
    }

    private function removeDatabase()
    {
        return DB::transaction(function () {
            DB::statement('DROP DATABASE IF EXISTS '.$this->database);
        });
    }
}
