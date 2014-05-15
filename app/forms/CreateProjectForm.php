<?php

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Date;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Identical;

class CreateProjectForm extends Form
{

    public function initialize()
    {
        $name = new Text('name');
        $name->setAttribute('placeholder', 'Name');
        $name->setAttribute('class', 'form-control');
        $name->setLabel('Project Name');
        $name->addValidator(new PresenceOf(array(
            'message' => 'Name is required'
        )));
        $this->add($name);

        $customer_id = new \Phalcon\Forms\Element\Select('customer_id', Customers::find('user_id IN('.$this->auth->getId().',0)'), array(
            'using' => array(
                'id', 'name'
            )));
        $customer_id->setLabel('Customer');
        $customer_id->setAttribute('class', 'form-control');
        $customer_id->addValidator(new PresenceOf(array('message' => 'Customer is required')));
        $this->add($customer_id);

        // CSRF
        $csrf = new Hidden('csrf');
        $csrf->setAttribute('value', $this->security->getSessionToken());
        $csrf->addValidator(new Identical(array(
            'value' => $this->security->getSessionToken(),
            'message' => 'CSRF validation failed'
        )));
        $this->add($csrf);

        $this->add(new Submit('Add', array(
            'class' => 'btn btn-lg btn-primary btn-block'
        )));
    }
}