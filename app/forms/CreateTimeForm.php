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

class CreateTimeForm extends Form
{

    public function initialize()
    {
        $start = new Date('start', array(
            'placeholder' => 'Start',
            'class' => 'form-control'
        ));

        $start->addValidator(new PresenceOf(array(
            'message' => 'Start is required'
        )));

        $this->add($start);


        $end = new Date('end', array(
            'placeholder' => 'End',
            'class' => 'form-control'
        ));

        $end->addValidator(new PresenceOf(array(
            'message' => 'End is required'
        )));

        $this->add($end);

        $tempnote = new Text('tempnote', array(
            'placeholder' => 'Note',
            'class' => 'form-control'
        ));

        $tempnote->addValidator(new PresenceOf(array(
            'message' => 'Note is required'
        )));

        $this->add($tempnote);

        // CSRF
        $csrf = new Hidden('csrf');

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