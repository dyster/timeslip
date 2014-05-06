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

        if (!$this->request->isPost()) {

            $time = Times::findFirstByid($id);
            if (!$time) {
                $this->flash->error("time was not found");

                return $this->dispatcher->forward(array(
                    "controller" => "times",
                    "action" => "index"
                ));
            }

            $this->view->id = $time->id;

            $this->tag->setDefault("id", $time->getId());
            $this->tag->setDefault("start", $time->getStart());
            $this->tag->setDefault("end", $time->getEnd());
            $this->tag->setDefault("user_id", $time->getUserId());
            $this->tag->setDefault("tempnote", $time->getTempnote());
            $this->tag->setDefault("project_id", $time->getProjectId());
            
        }
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
     * Saves a time edited
     *
     */
    public function saveAction()
    {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }

        $id = $this->request->getPost("id");

        $time = Times::findFirstByid($id);
        if (!$time) {
            $this->flash->error("time does not exist " . $id);

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }

        $time->setStart($this->request->getPost("start"));
        $time->setEnd($this->request->getPost("end"));
        $time->setUserId($this->request->getPost("user_id"));
        $time->setTempnote($this->request->getPost("tempnote"));
        $time->setProjectId($this->request->getPost("project_id"));
        

        if (!$time->save()) {

            foreach ($time->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "edit",
                "params" => array($time->id)
            ));
        }

        $this->flash->success("time was updated successfully");

        return $this->dispatcher->forward(array(
            "controller" => "times",
            "action" => "index"
        ));

    }

    /**
     * Deletes a time
     *
     * @param string $id
     */
    public function deleteAction($id)
    {

        $time = Times::findFirstByid($id);
        if (!$time) {
            $this->flash->error("time was not found");

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }

        if (!$time->delete()) {

            foreach ($time->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "search"
            ));
        }

        $this->flash->success("time was deleted successfully");

        return $this->dispatcher->forward(array(
            "controller" => "times",
            "action" => "index"
        ));
    }

    public function summaryAction()
    {
        $id = $this->auth->getId();
        $times = Times::find("user_id = $id");
        $output = array();
        foreach($times as $time) {
            $week = date('W', strtotime($time->getStart()));
            $year = date('Y', strtotime($time->getStart()));

            if(empty($output[$year]))
                $output[$year] = array();
            if(empty($output[$year][$week]))
                $output[$year][$week] = array('total' => 0, 'sums' => array());

            $output[$year][$week]['total'] += $time->getDuration();

            if(empty($output[$year][$week]['sums'][$time->getTempnote()]))
                $output[$year][$week]['sums'][$time->getTempnote()] = $time->getDuration();
            else
                $output[$year][$week]['sums'][$time->getTempnote()] += $time->getDuration();

            $output[$year][$week]['sums'][$time->getTempnote()] += $time->getDuration();
        }

        $this->view->output = $output;
    }

}
