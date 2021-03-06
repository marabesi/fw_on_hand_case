<?php

namespace Core;

class ApplicationWeb
{
    private $dependences = [];
    private $setedSetup = false;
    
    public function __construct(array $dependences)
    {
        $this->dependences = $dependences;
    }

    private function makeDefaultApplication()
    {
        $routes = (new ConfigParser())->get('routes');

        $sessionInjetc = new Session();
        $authInjetc = new Auth($sessionInjetc);
        $templateInject = new ViewModel($authInjetc, $sessionInjetc);

        $container  = new Container();
        $container->register('connection', (new DataBase)->getConnection())
                  ->register('request', new Request())
                  ->register('session', $sessionInjetc)
                  ->register('redirect', new Redirect($sessionInjetc))
                  ->register('template', $templateInject)
                  ->register('auth', $authInjetc);
                  
        $router = new Route($routes, $container);
        $router->run();
    }

    public function run()
    {
        if ($this->setedSetup === false) {
            $this->makeDefaultApplication();
        }
    }
}
