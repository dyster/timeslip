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

class CreateCustomerForm extends Form
{

    public function initialize()
    {
        $name = new Text('name');
        $name->setAttribute('placeholder', 'Name');
        $name->setAttribute('class', 'form-control');
        $name->setLabel('Customer Name');
        $name->addValidator(new PresenceOf(array(
            'message' => 'Name is required'
        )));
        $this->add($name);

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