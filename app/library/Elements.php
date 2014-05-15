<?php

/**
 * Elements
 *
 * Helps to build UI elements for the application
 */
class Elements extends Phalcon\Mvc\User\Component
{
    private $_headerMenu = array(
        'pull-left' => array(
            'Add Time' => array(
                'controller' => 'times',
                'action' => 'create'
            ),
            'Summary' => array(
                'controller' => 'times',
                'action' => 'summary'
            ),
            'Categorise' => array(
                'controller' => 'times',
                'action' => 'categorise'
            ),
            'Listify' => array(
                'controller' => 'times',
                'action' => 'listify'
            ),
            'Projects' => array(
                'controller' => 'projects',
                'action' => 'index'
            ),
            'Customers' => array(
                'controller' => 'customers',
                'action' => 'index'
            ),
        ),
        'pull-right' => array(
            'Logout' => array(
                'controller' => 'session',
                'action' => 'logout'
            )
        )
    );

    private $_tabs = array(
        'Invoices' => array(
            'controller' => 'invoices',
            'action' => 'index',
            'any' => false
        ),
        'Companies' => array(
            'controller' => 'companies',
            'action' => 'index',
            'any' => true
        ),
        'Products' => array(
            'controller' => 'products',
            'action' => 'index',
            'any' => true
        ),
        'Product Types' => array(
            'controller' => 'producttypes',
            'action' => 'index',
            'any' => true
        ),
        'Your Profile' => array(
            'controller' => 'invoices',
            'action' => 'profile',
            'any' => false
        )
    );

    /**
     * Builds header menu with left and right items
     *
     * @return string
     */
    public function getMenu()
    {
        $name = $this->auth->getName();

        $this->_headerMenu['pull-right'][$name] = array(
                'controller' => 'users',
                'action' => 'profile'
        );

        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();
        ?>

        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <?php echo Phalcon\Tag::linkTo(array('times/index', 'TimeSlip', 'class' => 'navbar-brand'));?>
                </div>
                <div class="navbar-collapse collapse">
                    <?php foreach ($this->_headerMenu as $position => $menu) {
                    echo '<ul class="nav navbar-nav ', $position, '">';
                        foreach ($menu as $caption => $option) {
                        if ($controllerName == $option['controller'] && $actionName == $option['action']) {
                        echo '<li class="active">';
                            } else {
                            echo '<li>';
                            }
                            echo Phalcon\Tag::linkTo($option['controller'].'/'.$option['action'], $caption);
                            echo '</li>';
                        }
                        echo '</ul>';
                    } ?>

                </div><!--/.nav-collapse -->
            </div>
        </nav>

    <?php
    }

    public function getTabs()
    {
        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();
        echo '<ul class="nav nav-tabs">';
        foreach ($this->_tabs as $caption => $option) {
            if ($option['controller'] == $controllerName && ($option['action'] == $actionName || $option['any'])) {
                echo '<li class="active">';
            } else {
                echo '<li>';
            }
            echo Phalcon\Tag::linkTo($option['controller'].'/'.$option['action'], $caption), '<li>';
        }
        echo '</ul>';
    }
}
