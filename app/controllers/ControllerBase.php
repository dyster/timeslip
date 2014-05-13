<?php

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;

class ControllerBase extends Controller
{
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        if($controller == 'session')
            return true;

        $identity = $this->auth->getIdentity();

        // If there is no identity available the user is redirected to index/index
        if (!is_array($identity)) {

            $this->flash->error('You must be logged in');

            $this->response->redirect('session/login');

            return false;
        }
    }
}
