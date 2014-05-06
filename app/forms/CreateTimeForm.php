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
            'placeholder' => 'Date',
            'class' => 'form-control'
        ));

        $start->setLabel('Start Date');

        $start->addValidator(new PresenceOf(array(
            'message' => 'Start-date is required'
        )));

        $this->add($start);

        $starttime = new Text('starttime', array(
            'placeholder' => 'Time',
            'class' => 'form-control'
        ));

        $starttime->setLabel('Start Time');

        $starttime->addValidator(new PresenceOf(array(
            'message' => 'Start-time is required'
        )));

        $this->add($starttime);


        $end = new Date('end', array(
            'placeholder' => 'Date',
            'class' => 'form-control'
        ));

        $end->setLabel('End Date');

        $end->addValidator(new PresenceOf(array(
            'message' => 'End-date is required'
        )));

        $this->add($end);

        $endtime = new Text('endtime', array(
            'placeholder' => 'Time',
            'class' => 'form-control'
        ));

        $endtime->setLabel('End Time');

        $endtime->addValidator(new PresenceOf(array(
            'message' => 'End-time is required'
        )));

        $this->add($endtime);

        $tempnote = new Text('tempnote', array(
            'placeholder' => 'Note',
            'class' => 'form-control'
        ));

        $tempnote->setLabel('Note');

        $tempnote->addValidator(new PresenceOf(array(
            'message' => 'Note is required'
        )));

        $this->add($tempnote);

        $projectid = new \Phalcon\Forms\Element\Select('project_id', Projects::find('user_id IN('.$this->auth->getId().',0)'), array(
            'using' => array(
                'id', 'name'
        )));
        $projectid->setLabel('Project');
        $projectid->setAttribute('placeholder', 'Placeholder?');
        $projectid->setAttribute('class', 'form-control');
        $projectid->addValidator(new PresenceOf(array('message' => 'Project is required')));
        $this->add($projectid);

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