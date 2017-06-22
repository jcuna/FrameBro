<?php
/**
 * Author: Jon Garcia
 * Date: 5/29/16
 */

namespace App\Core\Console;

use App\Core\Console\Exceptions\BadCommand;
use App\Core\Console\Exceptions\CommandException;
use App\Core\Exceptions\ModelException;
use App\Core\Http\Routing\Router;
use App\Core\Libraries\Inflect;
use App\Core\Migrations\Migrations;
use App\Core\Migrations\MigrationTable;
use App\Core\Migrations\Table;
use App\Core\Model\Loupe;
use App\Models\Role;
use App\Models\User;

class Cli extends Console
{

    /**
     * @var static
     */
    private static $instance;

    /**
     * The arguments sent to the method
     *
     * @var Argv
     */
    private $args;

    /**
     * List of registered commands
     *
     * @var array
     */
    private $commands = [

        "flush:views" => 'flushViews',
        "db:setup" => "dbSetup",
        "db:rollback" => "rollBack",
        "db:migrate" => "migrate",
        "new:user" => "newUser",
        "new:migration --table= --type= --help" => "newMigration",
        "routes" => "routes",
        "help" => "help"
    ];

    /**
     * @var array
     */
    private $noArgumentsCommands = [
        "migrate"
    ];

    /**
     * @var array
     */
    private $commandDescriptions = [
        "flushViews" => "Remove cached views and force regeneration.",
        "dbSetup" => "Create migration database.",
        "rollBack" => "Roll back database migrations.",
        "migrate" => "Run database migrations",
        "newUser" => "Creates a new user so you can login to the app for the first time.",
        "newMigration" => "Generate new migration file.",
        "routes" => "Show available routes",
        "help" => "",
    ];

    /**
     * Cli constructor.
     */
    public function __construct()
    {
        try {
            $this->registerCommands();
            \App::import(COMMANDS_FILE);
            $this->setArgs();
        } catch (\Throwable $t) {
            $this->handleException($t);
        }

        static::$instance = $this;
        $this->handleCall();
    }

    public function handleCall()
    {
        try {
            $command = $this->retrieveCommand();
            $this->args->processArgs($command);
            $this->execute($command);
        } catch (\Throwable $t) {
            $this->handleException($t);
        }
    }

    /**
     * @return Cli
     */
    public static function getInstance(): self
    {
        return static::$instance;
    }

    /**
     * @param \Throwable $t
     */
    private function handleException(\Throwable $t)
    {
        $this->error(get_class($t));
        $this->error($t->getMessage());
        if (! empty($t->getLine()) && ! empty($t->getFile())) {
            $this->error("file: {$t->getFile()}");
            $this->error("line: {$t->getLine()}");
        }
    }

    private function registerCommands()
    {
        $self = $this;
        foreach ($this->commands as $command => $method) {
            Commands::do($command, function(Argv $arguments) use ($self, $method) {
                if (in_array($method, $self->noArgumentsCommands)) {
                    call_user_func([$self, $method]);
                } else {
                    call_user_func([$self, $method], $arguments);
                }
            })->description($this->commandDescriptions[$method]);
        }
    }

    /**
     * @param Command $command
     */
    private function execute(Command $command)
    {
        $callable = $command->getCallable();

        if (! is_null($callable)) {
            $callable($this->args);
        } else {
            call_user_func([$command->getObject(), $command->getMethod()], $this->args);
        }
        $this->terminate(0);
    }

    /**
     *
     */
    public function setArgs(array $argv = null, int $argc = null)
    {
        if (is_null($argv) && is_null($argc)) {
            global $argc, $argv;
            unset($argv[0]);
            $argc--;
        }
        $this->args = new Argv(array_values($argv), $argc);
    }

    /**
     * Shows routes
     */
    public function routes()
    {
        \Kint::$display_called_from = false;
        \App::import(ROUTER_FILE);
        dd(Router::getRoutes());
    }

    /**
     * @param $time
     * @return bool
     * @throws CommandException
     */
    private function hasBeenMigrated(int $time, Loupe $migrationTable)
    {
        try {
            return $migrationTable->where("timestamp", $time)->count() === 1;
        } catch (ModelException $m) {
            throw new CommandException("Please run db:setup to create migrations table");
        }
    }

    /**
     * Run migrations
     *
     * @param bool $down
     */
    public function migrate($down = false)
    {
        $files = getDirectoryFiles(MIGRATIONS_PATH);

        if (is_null($files)) {
            $this->info("No migration files.");
            $this->terminate();
        }

        foreach ($files as $file) {
            if (strpos($file, 'Migration') > 0) {

                $namespace = '\\App\\Migrations\\';
                $fileName =  basename($file, '.php');

                $classStart = strpos($fileName, "_");
                $time = (int) substr($fileName, 0, $classStart);

                $migrationTable = new MigrationTable();
                if ($this->hasBeenMigrated($time, $migrationTable)) {
                    continue;
                }

                $className = substr($fileName, $classStart + 1);
                \App::import($file);

                $class = $namespace . $className;

                if (class_exists($class)) {

                    try {
                        /** @var Migrations $response */
                        $response = new $class($down);
                        $elapsedTime = $response->getElapsedTimeSum();
                        $migrationTable->insert([["timestamp" => $time]]);
                        $this->output("{$className} OK time: {$elapsedTime}", 'green');

                    } catch (\Exception $e) {

                        $func = $e->getTrace()[3]['function'];

                        $migration = basename($e->getTrace()[3]['file'], '.php');
                        $output = "Failed migrating $migration on $func" . PHP_EOL;
                        $output .= "Error: " . $e->getCode() . " " . $e->getMessage() . PHP_EOL;
                        $output .= "file: " . $e->getFile() . " line: " . $e->getLine();

                        $this->output($output, 'red');
                    }

                } else {
                    continue;
                }
            }
        }
    }

    /**
     * rollback migrations
     */
    public function rollBack()
    {
        $this->migrate(true);
    }

    /**
     * Deletes template files.
     */
    public function flushViews()
    {
        $files = glob(STORAGE_PATH .'views/*');
        $count = count($files);
        for ($i = 0; $i < $count; $i++) {
            if (is_file($files[$i]))
                unlink($files[$i]); // delete file
        }
        $this->success("{$count} views deleted.");
    }

    public function retrieveCommand(): Command
    {
        $command = Commands::getCommands()->get($this->args->command());
        if (is_null($command)) {
            throw new BadCommand("Command {$this->args->command()} does not exist");
        }

        return $command;
    }

    /**
     * Show all commands and description
     */
    public function help()
    {
        $length = 30;
        $space = $this->getTableSpacing($length, "Command");

        $this->info("Command{$space}Description");
        Commands::getCommands()->each(function (Command $command) use ($length) {
            if ($command->getName() === "help") return;
            $space = $this->getTableSpacing($length, $command->getName());
            $this->output($command->getName() . $space . $command->getDescription(), "green");
            $command->getParameters()->each(function (array $params) {
                $this->increasePadding();
                $required = $params["required"] ? "required" : "optional";
                $name = $params["type"] === "argument" ? "  {$params["original"]}" : $params["original"];
                $this->line("$name {{$required}}");
                $this->resetPadding();
            });
        });
    }

    /**
     * @param int $length
     * @param $word
     * @return string
     */
    private function getTableSpacing(int $length, $word): string
    {
        $space = "";
        for ($i = strlen($word); $i <= $length; $i++) {
            $space .= " ";
        }

        return $space;
    }

    /**
     * @param Argv $argv
     */
    public function newMigration(Argv $argv)
    {
        if ($argv->get("help")) {
            $this->increasePadding();
            $this->output("--table table_name -- is the name of the table in database.", "cyan");
            $this->output("--type create/update -- Allowed options are create/update.", "cyan");
            $this->terminate();
        }
        if (! preg_match("/^[a-z0-9_]+$/", $argv->get("table"))) {
            throw new \InvalidArgumentException("table should contain only lower case characters, numbers and underscores");
        }
        if (! in_array($argv->get("type"), ["create", "update"])) {
            throw new \InvalidArgumentException("--type must be either create or update");
        }

        $this->processNewMigration($argv);
    }

    /**
     * @param Argv $argv
     */
    private function processNewMigration(Argv $argv)
    {
        $type = $argv->get("type");
        $words = explode("_", "{$type}_".$argv->get("table"));
        $word = implode("", array_map(function ($word) { return ucfirst($word);}, $words));
        $className = Inflect::singularize($word)."Migration";
        $fileName = MIGRATIONS_PATH . time() . "_{$className}.php";
        $contents = file_get_contents(CORE_PATH."templates/{$type}_table.inc");
        $output = $this->getUpdatedTemplate($contents, $className, $argv->get("table"));

        file_put_contents($fileName, $output);
        sleep(1);
        $this->info("{$fileName} created successfully.");
    }

    /**
     * @param string $contents
     * @param string $className
     * @param string $table
     * @return string
     */
    private function getUpdatedTemplate(string $contents, string $className, string $table)
    {
        $find = '{TABLENAME}';
        $replace = "'$table'";

        $patterns = ["/{DATE}/", "/{TIME}/", "/{CLASSNAME}/"];
        $replacements = [date("m/d/y"), date("h:m a"), $className];

        $output = preg_replace_callback(
            '/{TABLENAME}/',
            function($match) use ($find, $replace) {
                return str_replace($find, $replace, $match[0]);
            },
            preg_replace($patterns, $replacements, $contents)
        );

        return "<?php\n{$output}";
    }

    /**
     * Setup migrations table
     */
    public function dbSetup()
    {
        new class extends Migrations {

            protected function up()
            {
                $this->create('migrations', function(Table $t) {
                    $t->incremental("id")->unsigned();
                    $t->bigInteger("timestamp")->unique();
                });
            }

            protected function down()
            {
                $this->drop("migrations");
            }
        };
        $this->info("Migrations table successfully created");
    }

    public function newUser()
    {
        $username = $this->ask("Type desired username");
        $mail = $this->ask("Type desired email");
        $name = $this->ask("Type first name");
        $last = $this->ask("Type last name");
        $password = $this->ask("Type password");

        $user = new User([
            "username" => $username,
            "fname" => $name,
            "lname" => $last,
            "email" => $mail,
            "password" => User::encrypt($password)
        ]);

        try {
            $user->transaction(function(User $user) {
                if ($user->save()) {
                    $role = (new Role())->where("name", "Super Admin")->first(["id"]);
                    if ($user->morphTo('roles')->save(['user_id' => $user->uid, 'role_id' => $role->id])) {
                        $this->info("user created successfully");
                    }
                }
            });
        } catch (\Throwable $t) {
            dd($t);
        }

    }

    /**
     * @param int $code
     */
    private function terminate(int $code = 0)
    {
        exit($code);
    }
}