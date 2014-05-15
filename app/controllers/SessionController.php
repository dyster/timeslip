<?php

use Exception as AuthException;

class SessionController extends ControllerBase
{
    public function loginAction()
    {
        $form = new LoginForm();

        try {

            if (!$this->request->isPost()) {

                if ($this->auth->hasRememberMe()) {
                    return $this->auth->loginWithRememberMe();
                }
            } else {
                    if ($form->isValid($this->request->getPost()) == false) {
                        foreach ($form->getMessages() as $message) {
                            $this->flash->error($message);
                        }
                    } else {
                        $this->auth->check(array(
                            'email' => $this->request->getPost('email'),
                            'password' => $this->request->getPost('password'),
                            'remember' => $this->request->getPost('remember')
                        ));

                        return $this->response->redirect('times');
                    }
            }
        } catch (AuthException $e) {
            $this->flash->error($e->getMessage());
        }

        $this->view->form = $form;
    }

    public function forgotPasswordAction()
    {
        $form = new ForgotPasswordForm();

        if ($this->request->isPost()) {

            if ($form->isValid($this->request->getPost()) == false) {
                foreach ($form->getMessages() as $message) {
                    $this->flash->error($message);
                }
            } else {

                $user = Users::findFirstByEmail($this->request->getPost('email'));
                if (!$user) {
                    $this->flash->success('There is no account associated to this email');
                } else {

                    $resetPassword = new ResetPasswords();
                    $resetPassword->usersId = $user->id;
                    if ($resetPassword->save()) {
                        $this->flash->success('Success! Please check your messages for an email reset password');
                    } else {
                        foreach ($resetPassword->getMessages() as $message) {
                            $this->flash->error($message);
                        }
                    }
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * Allow a user to signup to the system
     */
    public function signupAction()
    {
        $form = new SignUpForm();

        if ($this->request->isPost()) {

            if ($form->isValid($this->request->getPost()) != false) {

                $user = new Users();

                $user->assign(array(
                    'name' => $this->request->getPost('name', 'striptags'),
                    'email' => $this->request->getPost('email'),
                    'password' => $this->security->hash($this->request->getPost('password')),
                    'profilesId' => 0,
                    'banned' => 'N',
                    'suspended' => 'N',
                    'active' => 'Y'
                ));

                if ($user->save()) {

                    $this->flash->success("You have successfully registered! Please login");

                    return $this->dispatcher->forward(array(
                        'controller' => 'session',
                        'action' => 'login'
                    ));
                }
                foreach($user->getMessages() as $message) {
                    $this->flash->error($message);
                }

            }
        }

        $this->view->form = $form;
    }

    public function logoutAction()
    {
        $this->auth->remove();

        return $this->response->redirect('session/login');
    }
}

