<?php

use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;

class CustomersController extends ControllerBase
{

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->persistent->parameters = null;
    }

    /**
     * Searches for customers
     */
    public function searchAction()
    {

        $numberPage = 1;
        if ($this->request->isPost()) {
            $query = Criteria::fromInput($this->di, "Customers", $_POST);
            $this->persistent->parameters = $query->getParams();
        } else {
            $numberPage = $this->request->getQuery("page", "int");
        }

        $parameters = $this->persistent->parameters;
        if (!is_array($parameters)) {
            $parameters = array();
        }
        $parameters["order"] = "id";

        $customers = Customers::find($parameters);
        if (count($customers) == 0) {
            $this->flash->notice("The search did not find any customers");

            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "index"
            ));
        }

        $paginator = new Paginator(array(
            "data" => $customers,
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
     * Edits a customer
     *
     * @param string $id
     */
    public function editAction($id)
    {

        if (!$this->request->isPost()) {

            $customer = Customers::findFirstByid($id);
            if (!$customer) {
                $this->flash->error("customer was not found");

                return $this->dispatcher->forward(array(
                    "controller" => "customers",
                    "action" => "index"
                ));
            }

            $this->view->id = $customer->id;

            $this->tag->setDefault("id", $customer->getId());
            $this->tag->setDefault("user_id", $customer->getUserId());
            $this->tag->setDefault("name", $customer->getName());
            
        }
    }

    /**
     * Creates a new customer
     */
    public function createAction()
    {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "index"
            ));
        }

        $customer = new Customers();

        $customer->setUserId($this->request->getPost("user_id"));
        $customer->setName($this->request->getPost("name"));
        

        if (!$customer->save()) {
            foreach ($customer->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "new"
            ));
        }

        $this->flash->success("customer was created successfully");

        return $this->dispatcher->forward(array(
            "controller" => "customers",
            "action" => "index"
        ));

    }

    /**
     * Saves a customer edited
     *
     */
    public function saveAction()
    {

        if (!$this->request->isPost()) {
            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "index"
            ));
        }

        $id = $this->request->getPost("id");

        $customer = Customers::findFirstByid($id);
        if (!$customer) {
            $this->flash->error("customer does not exist " . $id);

            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "index"
            ));
        }

        $customer->setUserId($this->request->getPost("user_id"));
        $customer->setName($this->request->getPost("name"));
        

        if (!$customer->save()) {

            foreach ($customer->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "edit",
                "params" => array($customer->id)
            ));
        }

        $this->flash->success("customer was updated successfully");

        return $this->dispatcher->forward(array(
            "controller" => "customers",
            "action" => "index"
        ));

    }

    /**
     * Deletes a customer
     *
     * @param string $id
     */
    public function deleteAction($id)
    {

        $customer = Customers::findFirstByid($id);
        if (!$customer) {
            $this->flash->error("customer was not found");

            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "index"
            ));
        }

        if (!$customer->delete()) {

            foreach ($customer->getMessages() as $message) {
                $this->flash->error($message);
            }

            return $this->dispatcher->forward(array(
                "controller" => "customers",
                "action" => "search"
            ));
        }

        $this->flash->success("customer was deleted successfully");

        return $this->dispatcher->forward(array(
            "controller" => "customers",
            "action" => "index"
        ));
    }

}
