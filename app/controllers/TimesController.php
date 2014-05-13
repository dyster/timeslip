<?php

use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;

class TimesController extends ControllerBase
{

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->persistent->parameters = null;

        $identity = $this->auth->getIdentity();

        if($this->request->isPost()) {
            $halftime = Times::findFirst('end IS NULL');

            if($halftime !== false) {
                $halftime->setEnd(date(DATE_ATOM));
                if (!$halftime->save()) {
                    foreach ($halftime->getMessages() as $message) {
                        $this->flash->error($message);
                    }
                }
            }

            if($this->request->getPost("action") == 'start') {
                $time = new Times();
                $time->setStart(date(DATE_ATOM));
                $time->setUserId($identity['id']);
                $time->setTempnote($this->request->getPost("tempnote"));
                $time->setEnd('0000-00-00 00:00:00');
                $time->setProjectId(0);
                if (!$time->save()) {
                    foreach ($time->getMessages() as $message) {
                        $this->flash->error($message);
                    }
                }
            }

        }



        $halftime = Times::findFirst('end IS NULL');
        $recents = Times::find(array("order" => "end DESC", "limit" => 5, "columns" => "tempnote", "group" => "tempnote"));

        if($halftime === false)
            $state = 'stopped';
        else
            $state = 'running';

        $this->view->setVars(compact('recents', 'state', 'halftime'));
    }

    public function categoriseAction()
    {
        if($this->request->isPost()) {
            foreach($this->request->getPost() as $id => $pid) {
                if($pid > 0) {
                    $time = Times::findFirst($id);
                    self::checkTime($time);
                    $time->setProjectId($pid);
                    if (!$time->save()) {
                        foreach ($time->getMessages() as $message) {
                            $this->flash->error($message);
                        }
                    }
                }
            }
        }

        $projects = array(0 => 'Unknown');
        foreach(Projects::find('user_id = '.$this->auth->getId()) as $project) {
            $projects[$project->getId()] = $project->getName();
        }
        $this->view->projects = $projects;

        $times = Times::find('user_id = ' . $this->auth->getId() . ' AND project_id = 0');
        $this->view->times = $times;
    }

    public function listifyAction()
    {
        $param = $this->dispatcher->getParam(0);
        if(empty($param)) {
            $times = Times::find(array(
                'order' => 'start',
                'user_id = '.$this->auth->getId()
            ));
        } else {
            $times = Times::find(array(
                'order' => 'start',
                "project_id = $param AND user_id = ".$this->auth->getId()
            ));
        }

        foreach($times as $time) {
            $week = date('W', strtotime($time->getStart()));
            $year = date('Y', strtotime($time->getStart()));
            $day = date('d', strtotime($time->getStart()));
            $output["$year - Week $week"][$day][] = $time;
        }

        $this->view->weeks = $output;

    }

    /**
     * Searches for times
     */
    public function searchAction()
    {

        $numberPage = 1;
        if ($this->request->isPost()) {
            $query = Criteria::fromInput($this->di, "Times", $_POST);
            $this->persistent->parameters = $query->getParams();
        } else {
            $numberPage = $this->request->getQuery("page", "int");
        }

        $parameters = $this->persistent->parameters;
        if (!is_array($parameters)) {
            $parameters = array();
        }
        $parameters["order"] = "id";

        $times = Times::find($parameters);
        if (count($times) == 0) {
            $this->flash->notice("The search did not find any times");

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }

        $paginator = new Paginator(array(
            "data" => $times,
            "limit"=> 10,
            "page" => $numberPage
        ));

        $this->view->page = $paginator->getPaginate();
    }

    /**
     * Edits a time
     *
     * @param string $id
     */
    public function editAction($id)
    {
        $time = Times::findFirstByid($id);

        self::checkTime($time);

        if ($this->request->isPost()) {

            $time->setStart($this->request->getPost("start"));
            $time->setEnd($this->request->getPost("end"));
            $time->setTempnote($this->request->getPost("tempnote"));
            $time->setProjectId($this->request->getPost("project_id"));

            if (!$time->save()) {
                $this->flash->error($time->getMessages());
            }
            else {
                $this->flash->success("time was updated successfully");
            }

        }

        $this->view->form = new EditTimeForm($time);
    }

    /**
     * Creates a new time
     */
    public function createAction()
    {
        $form = new CreateTimeForm();

        if ($this->request->isPost()) {

            if ($form->isValid($this->request->getPost()) == false) {
                foreach ($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {
                $time = new Times();

                $start = new DateTime($this->request->getPost("start") . " " . self::fixTime($this->request->getPost("starttime")));
                $end = new DateTime($this->request->getPost("end") . " " . self::fixTime($this->request->getPost("endtime")));
                $form->get('starttime')->setDefault($start->format('H:m:s'));
                $form->get('endtime')->setDefault($end->format('H:m:s'));
                $time->setStart($start->format(DATE_ATOM));
                $time->setEnd($end->format(DATE_ATOM));
                $time->setUserId($this->auth->getId());
                $time->setTempnote($this->request->getPost("tempnote"));
                $time->setProjectId($this->request->getPost("project_id"));

                if (!$time->save()) {
                    foreach ($time->getMessages() as $message) {
                        $this->flash->error($message);
                    }
                }
                else
                    $this->flash->success("time was created successfully");
            }


        }


        $this->view->form = $form;
    }

    /*
     * Checks if time is owned by user
     */
    private function checkTime($time)
    {
        if (!$time) {
            $this->flash->error("Time does not exist");

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }

        if($time->getUserId() != $this->auth->getId()) {
            $this->flash->error("This time does not belong to you!");

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }
    }

    private function fixTime($str)
    {
        switch(strlen($str)) {
            case 2:
                return $str . ":00:00";
            case 4:
                return $str[0].$str[1].':'.$str[2].$str[3].':00';
        }
        return $str;
    }

    /**
     * Deletes a time
     *
     * @param string $id
     */
    public function deleteAction($id)
    {

        $time = Times::findFirstByid($id);

        self::checkTime($time);

        if($this->request->isPost()) {
            if($this->request->getPost('confirm') == 1) {

                if(!$time->delete()) {
                    $this->flash->error($time->getMessages());
                }
                else
                    $this->flash->success('Time was deleted successfully');

                return $this->dispatcher->forward(array(
                    "controller" => "times",
                    "action" => "listify",
                    "params" => array()
                ));
            }

        }

        $this->view->time = $time;
    }

    public function summaryAction()
    {
        $id = $this->auth->getId();
        $times = Times::find("user_id = $id");
        $output = array();
        foreach($times as $time) {
            $day = date('D', strtotime($time->getStart()));
            $week = date('W', strtotime($time->getStart()));
            $year = date('Y', strtotime($time->getStart()));

            $dur = $time->getDuration();

            if(empty($output[$year][$week]))
                $output[$year][$week] = array('total' => 0, 'days' => array(), 'projects' => array());

            if(empty($output[$year][$week]['projects'][$time->getTempnote()]))
                $output[$year][$week]['projects'][$time->getTempnote()] = 0;

            if(empty($output[$year][$week]['days'][$day]))
                $output[$year][$week]['days'][$day] = array('total' => 0, 'projects' => array());

            if(empty($output[$year][$week]['days'][$day]['projects'][$time->getTempnote()]))
                $output[$year][$week]['days'][$day]['projects'][$time->getTempnote()] = 0;



            $output[$year][$week]['total'] += $dur;
            $output[$year][$week]['days'][$day]['total'] += $dur;
            $output[$year][$week]['days'][$day]['projects'][$time->getTempnote()] += $time->getDuration();
            $output[$year][$week]['projects'][$time->getTempnote()] += $time->getDuration();
        }

        $this->view->output = $output;
    }

}
