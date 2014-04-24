<?php

use Phalcon\Mvc\Model;

/**
 * SuccessLogins
 * This model registers successfull logins registered users have made
 */
class SuccessLogins extends Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $usersId;

    /**
     *
     * @var string
     */
    public $ipAddress;

    /**
     *
     * @var string
     */
    public $userAgent;

    /**
     * @var string
     */
    public $token;

    public function initialize()
    {
        $this->belongsTo('usersId', 'Users', 'id', array(
            'alias' => 'user'
        ));
    }
}
