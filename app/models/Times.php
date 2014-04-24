<?php

class Times extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $start;

    /**
     *
     * @var string
     */
    protected $end;

    /**
     *
     * @var integer
     */
    protected $user_id;

    /**
     *
     * @var string
     */
    protected $tempnote;

    /**
     *
     * @var integer
     */
    protected $project_id;

    /**
     * Method to set the value of field start
     *
     * @param  string $start
     * @return $this
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Method to set the value of field end
     *
     * @param  string $end
     * @return $this
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Method to set the value of field user_id
     *
     * @param  integer $user_id
     * @return $this
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Method to set the value of field tempnote
     *
     * @param  string $tempnote
     * @return $this
     */
    public function setTempnote($tempnote)
    {
        $this->tempnote = $tempnote;

        return $this;
    }

    /**
     * Method to set the value of field project_id
     *
     * @param  integer $project_id
     * @return $this
     */
    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;

        return $this;
    }

    /**
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field start
     *
     * @return string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Returns the value of field end
     *
     * @return string
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Returns the value of field user_id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Returns the value of field tempnote
     *
     * @return string
     */
    public function getTempnote()
    {
        return $this->tempnote;
    }

    /**
     * Returns the value of field project_id
     *
     * @return integer
     */
    public function getProjectId()
    {
        return $this->project_id;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo("user_id", "Users", "id", NULL);
        $this->belongsTo("user_id", "Projects", "id", NULL);
        $this->skipAttributesOnCreate(array('end', 'project_id'));
    }

}
