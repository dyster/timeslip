<?php

use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Timeslip\Models\Times;

class TimesController extends ControllerBase
{

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->persistent->parameters = null;

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

                $time->setTempnote($this->request->getPost("tempnote"));
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

    public function listifyAction()
    {
        $param = $this->dispatcher->getParam(0);
        if(empty($param)) {
            $times = Times::find(array(
                'order' => 'start'
            ));
        } else {
            $times = Times::find(array(
                'order' => 'start',
                "project_id = $param"
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
     * Displayes the creation form
     */
    public function newAction()
    {

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

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                "controller" => "times",
                "action" => "index"
            ));
        }

        $time = new Times();

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
                "action" => "new"
            ));
        }

        $this->flash->success("time was created successfully");

        return $this->dispatcher->forward(array(
            "controller" => "times",
            "action" => "index"
        ));

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

}
