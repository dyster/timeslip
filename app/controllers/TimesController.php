<?php

use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\NativeArray as Paginator;

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

            $location = self::getTimeZone($this->request->getPost('lat'), $this->request->getPost('lng'));

            $halftime = Times::findFirst('end IS NULL');

            if($halftime !== false) {
                $halftime->setEnd(date(DATE_ATOM));
                $halftime->setEndLocation($location);
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
                $time->setEndLocation(null);
                $time->setProjectId(0);
                $time->setStartLocation($location);
                if (!$time->save()) {
                    foreach ($time->getMessages() as $message) {
                        $this->flash->error($message);
                    }
                }
            }

        }



        $halftime = Times::findFirst('end IS NULL');
        //$recents = Times::find(array("order" => "end DESC", "limit" => 5, "columns" => "tempnote", "group" => "tempnote"));
        $row = 0;
        $i = 0;
        $recents = array();
        foreach(Times::find(array('user_id = '.$this->auth->getId(), "order" => "end DESC", "limit" => 9, "columns" => "tempnote", "group" => "tempnote")) as $recent) {
            $recents[$row][] = $recent;
            $i++;
            if($i==3) {
                $i=0;
                $row++;
            }
        }
        if($halftime === false)
            $state = 'stopped';
        else
            $state = 'running';

        $this->view->setVars(compact('recents', 'state', 'halftime'));
    }

    private function getTimeZone($latitude, $longitude)
    {
        $location = array();
        $googleapi = "AIzaSyA592ILmdLBk32xtzTyWaUPN1P_6tOuUYw";

        $timestamp = time();
        //$arr = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&key=$googleapi&result_type=postal_town&location_type=APPROXIMATE"));
        //$status = $arr->status;
        //if($status == "OK") {
        //    print_r($arr->results[0]->address_components);
        //}

        $timezonearr = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/timezone/json?location=$latitude,$longitude&timestamp=$timestamp&key=$googleapi"));
        $location["timezone"] = $timezonearr->timeZoneId;
        //$offset = $timezonearr->dstOffset + $timezonearr->rawOffset;
        $location["latitude"] = $latitude;
        $location["longitude"] = $longitude;
        return $location;
    }

    public function getGoogleLocationAction($latitude, $longitude)
    {
        $this->view->disable();
        $googleapi = "AIzaSyA592ILmdLBk32xtzTyWaUPN1P_6tOuUYw";
        $arr = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=$latitude,$longitude&key=$googleapi&result_type=postal_town&location_type=APPROXIMATE"));
        $status = $arr->status;
        if($status == "OK") {
            echo $arr->results[0]->address_components[0]->short_name;
        }
        else
            print_r($status);
    }

    public function getGoogleCoordsAction($addr)
    {
        $this->view->disable();
        $googleapi = "AIzaSyA592ILmdLBk32xtzTyWaUPN1P_6tOuUYw";
        $arr = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=$addr&key=$googleapi"));
        $status = $arr->status;
        if($status == "OK") {
            foreach($arr->results as $result){
                $out[] = array($result->formatted_address, $result->geometry->location->lat, $result->geometry->location->lng);
                //echo "\n".$result->formatted_address . " " . $result->geometry->location->lat . " " . $result->geometry->location->lng;
            }
            echo json_encode($out);
        }
        else
            print_r($status);
    }

    public function categoriseAction()
    {
        $this->tag->appendTitle(" - Categorise");
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
        if(!count($times))
            $this->view->disableLevel(Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
    }

    public function listifyAction()
    {
        $this->tag->appendTitle(" - Listify");
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

        $output = array();
        foreach($times as $time) {
            $week = date('W', strtotime($time->getStart()));
            $year = date('Y', strtotime($time->getStart()));
            $day = date('d', strtotime($time->getStart()));
            $output["$year - Week $week"][$day][] = $time;
        }

        $currentPage = $this->request->getQuery('page', 'int');

        $paginator = new Paginator(
            array(
                "data" => $output,
                "limit"=> 5,
                "page" => $currentPage
            )
        );
        $page = $paginator->getPaginate();

        $this->view->weeks = $output;
        $this->view->page = $page;
    }

    /**
     * Searches for times
     */
    public function searchAction()
    {
        $this->tag->appendTitle(" - Search");

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
        $this->tag->appendTitle(" - Edit Time");

        $time = Times::findFirstByid($id);

        self::checkTime($time);

        if ($this->request->isPost()) {

            $time->setStart($this->request->getPost("start"));
            $time->setEnd($this->request->getPost("end"));
            $time->setTempnote($this->request->getPost("tempnote"));
            $time->setProjectId($this->request->getPost("project_id"));

            if (!$time->save()) {
                foreach ($time->getMessages() as $message) {
                    $this->flash->error($message);
                }
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
        $this->tag->appendTitle(" - Add Time");

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
        $this->tag->appendTitle(" - Delete Time");
        $time = Times::findFirstByid($id);

        self::checkTime($time);

        if($this->request->isPost()) {
            if($this->request->getPost('confirm') == 1) {

                if(!$time->delete()) {
                    foreach ($time->getMessages() as $message) {
                        $this->flash->error($message);
                    }
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
        $this->tag->appendTitle(" - Summary");

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
