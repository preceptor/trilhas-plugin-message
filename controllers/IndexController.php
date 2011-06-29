<?php
class Message_IndexController extends Tri_Controller_Action
{
	public function init()
    {
        parent::init();
        $this->view->title = array("Message");
    }

    public function indexAction()
    {
        $page    = Zend_Filter::filterStatic($this->_getParam('page'), 'int');
        $query   = Zend_Filter::filterStatic($this->_getParam('query'), 'stripTags');
        $table   = new Message_Model_DbTable_Message();
        $userId  = Zend_Auth::getInstance()->getIdentity()->id;
        $session = new Zend_Session_Namespace('data');
        $options = array('userId' => $userId,
                         'classroomId' => $session->classroom_id,
                         'query' => $query);
        
        $paginator = new Tri_Paginator($table->received($userId, $options), $page);

        $this->view->title[] = "Inbox";
        $this->view->data    = $paginator->getResult();
    }

    public function sentAction()
    {
        $page    = Zend_Filter::filterStatic($this->_getParam('page'), 'int');
        $query   = Zend_Filter::filterStatic($this->_getParam('query'), 'stripTags');
        $table   = new Message_Model_DbTable_Message();
        $userId  = Zend_Auth::getInstance()->getIdentity()->id;
        $session = new Zend_Session_Namespace('data');
        $options = array('userId' => $userId,
                         'classroomId' => $session->classroom_id,
                         'query' => $query);

        $paginator = new Tri_Paginator($table->sended($userId, $options), $page);

        $this->view->title[] = "Sent";
        $this->view->data    = $paginator->getResult();
        $this->view->sent    = true;
        $this->render('index');
    }

    public function formAction()
    {
        $id            = Zend_Filter::filterStatic($this->_getParam('id'), 'int');
        $form          = new Message_Form_Message();
        $classroomUser = new Tri_Db_Table('classroom_user');
        $session       = new Zend_Session_Namespace('data');

        $select = $classroomUser->select(true)
                                ->setIntegrityCheck(false)
                                ->join('user', 'classroom_user.user_id = user.id', array('user.name','user.id','user.image'))
                                ->where('(classroom_user.classroom_id = ?', $session->classroom_id)
                                ->where('user.role IN(?))', array('student','teacher'))
                                ->where('user.status = ?', 'active')
                                ->where('user.id <> ?', Zend_Auth::getInstance()->getIdentity()->id)
                                ->order('name');

        $this->view->users = $classroomUser->fetchAll($select);

        if ($id) {
            $table = new Tri_Db_Table('message');
            $row   = $table->find($id)->current();

            if ($row) {
                $receiverTable = new Tri_Db_Table('message_receiver');
                $data = $receiverTable->fetchAll(array('message_id = ?' => $id));
                $form->populate($row->toArray());
                $form->getElement('description')
                     ->removeFilter('stringTrim')
                     ->setValue("\n\n\n----- " . $this->view->date($row->created)
                                . " -----\n>\n"
                                . str_replace("\n", "\n> ", '> ' . $row->description)
                                . "\n>");

                if (Zend_Auth::getInstance()->getIdentity()->id == $row->user_id) {
                    $this->view->user = array();
                    foreach ($data as $value) {
                        $user[] = $value->user_id;
                    }
                } else {
                    $user = array($row->user_id);
                }
                $this->view->user = Zend_Json::encode($user);
            }
        }

        $this->view->form = $form;
    }

    public function viewAction()
    {
        $id   = Zend_Filter::filterStatic($this->_getParam('id'), 'int');
        $form = new Message_Form_Message();

        if ($id) {
            $table = new Message_Model_DbTable_Message();
            $this->view->data = $table->fetchRow($table->view($id));

            $receiverTable = new Tri_Db_Table('message_receiver');
            $row = $receiverTable->fetchRow(array('message_id = ?' => $id,
                                                  'user_id = ?' => Zend_Auth::getInstance()->getIdentity()->id));
            if ($row) {
                $row->read = 1;
                $row->save();
            }
        }

        $this->view->form = $form;
    }
    
    public function saveAction()
    {
        $form  = new Message_Form_Message();
        $table = new Message_Model_DbTable_Message();
        $receiverTable = new Tri_Db_Table('message_receiver');
        $session = new Zend_Session_Namespace('data');
        $data  = $this->_getAllParams();

        if ($form->isValid($data)) {
            $data = $form->getValues();
            $users = $this->_getParam('users');
            if (count($users)) {
                unset($data['id']);
                $data['user_id'] = Zend_Auth::getInstance()->getIdentity()->id;
                $id = $table->createRow($data)->save();

                $description = $data['description'];
                $data = array('message_id' => $id);

                foreach ($users as $user) {
                    $data['user_id'] = $user;
                    $receiverTable->createRow($data)->save();
                }

                if ($this->_getParam('email')) {
                    $this->sendMail($users, $description);
                }
                $this->_helper->_flashMessenger->addMessage('Success');
                $this->_redirect('message/index/sent');
            }
        }

        $this->view->messages = array('Error');
        $this->view->form = $form;
        $this->render('form');
    }

    public function deleteAction()
    {
        $table    = new Tri_Db_Table('message');
        $receiver = new Tri_Db_Table('message_receiver');
        $ids      = $this->_getParam('messages');
        $sent     = $this->_getParam('sent');
        $userId   = Zend_Auth::getInstance()->getIdentity()->id;

        if (count($ids)) {
            foreach ($ids as $id) {
                if ($sent) {
                    $table->update(array('status' => 'delete'), array('id = ?' => $id));
                } else {
                    $receiver->delete(array('message_id = ?' => $id,
                                            'user_id = ?' => $userId));
                }
            }
        }
        
        $this->_helper->_flashMessenger->addMessage('Success');

        if ($sent) {
            $this->_redirect('message/index/sent');
        } else {
            $this->_redirect('message/index/');
        }
    }

    public function sendMail($ids, $description)
    {
        $userTable = new Tri_Db_Table('user');
        $users     = $userTable->find($ids);

        $body = Zend_Auth::getInstance()->getIdentity()->name . "\n\n" . $description;

        $mail = new Zend_Mail('utf-8');
        $mail->setSubject($this->view->translate('New message'));
        $mail->setBodyHtml(nl2br($body));

        foreach ($users as $user) {
            $mail->addBcc($user->email);
        }

        try {
            $mail->send();
        }catch(Exception $e) {
            Zend_Registry::get('Zend_Log')->log(print_f($e,true));
        }
    }
}