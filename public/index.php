<?php

namespace {

    use App\Core\ExceptionHandler;
    use Dotenv\Dotenv;
    use App\Services\BootstrapService;

    require '../app/Core/App.php';

    App::startErrorReporting();
    App::setAutoLoader('vendor/autoload');

    App::defineSettings([
        "mail" => "app/config/mail",
        "connections" => "app/config/connections",
        "aliases" => "app/config/aliases",
    ]);

    App::main(function () {
        /** @var App $this */
        $this->import('app/Core/Libraries/globals');

        if ($this->fileExists('.env')) {

            $env = new Dotenv(ABSOLUTE_PATH);
            $env->load();
            $this->processConfigs();

            $this->setTimeZone(getenv("TIMEZONE"));

            if (! $this->isEnv('dev', 'development')) {
                $this->stopErrorReporting();
            }

            try {
                BootstrapService::boot($this);
                $this->startRequest();
                $this->startRouter($this->getRequest());
                $this->getRouter()
                    ->post(
                        'AjaxController',
                        'App\\Core\\Ajax\\AjaxController@jsonResponder'
                    );
                $this->fireApp();
            } catch (\Throwable $e) {
                new ExceptionHandler($e);
            }
        } else {
            print "no env file";
            exit(1);
        }
    });
    exit;
}