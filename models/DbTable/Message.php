<?php
/**
 * Trilhas - Learning Management System
 * Copyright (C) 2005-2010  Preceptor Educação a Distância Ltda. <http://www.preceptoead.com.br>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @category   Message
 * @package    Message_Model_DbTable
 * @copyright  Copyright (C) 2005-2011  Preceptor Educação a Distância Ltda. <http://www.preceptoead.com.br>
 * @license    http://www.gnu.org/licenses/  GNU GPL
 */
class Message_Model_DbTable_Message extends Tri_Db_Table
{
    protected $_name = "message";

    public function view($id)
    {
        $select = $this->select(true)
                       ->setIntegrityCheck(false)
                       ->columns('COUNT(message_receiver.user_id)-1 as total')
                       ->join('message_receiver', 'message_receiver.message_id = message.id', '*')
                       ->join('user', 'message.user_id = user.id', array('name','image','email'))
                       ->join(array('u' => 'user'), 'message_receiver.user_id = u.id', array('rname' => 'name','rimage' => 'image'))
                       ->where('message.id = ?', $id)
                       ->group('message.id')
                       ->order('id DESC');

        return $select;
    }

    public function received($userId, $options = null)
    {
        $select = $this->select(true)
                       ->setIntegrityCheck(false)
                       ->join('message_receiver', 'message_receiver.message_id = message.id')
                       ->join('user', 'message.user_id = user.id', array('name','image','email'))
                       ->where('message_receiver.user_id = ?', $userId)
                       ->group('message.id')
                       ->order('id DESC');
        
        if (isset($options['classroomId']) && $options['classroomId']) {
            $select->where('message.classroom_id = ? OR message.classroom_id IS NULL', $options['classroomId']);
        }

        if (isset($options['senderId']) && $options['senderId']) {
            $select->where('message.user_id = ?', $options['senderId']);
        }

        if (isset($options['query']) && $options['query']) {
            $select->where('(message.description LIKE(?) OR user.name LIKE(?))', "%" . $options['query'] . "%");
        }

        return $select;
    }

    public function sended($userId, $options = null)
    {
        $select = $this->select(true)
                       ->setIntegrityCheck(false)
                       ->columns('COUNT(message_receiver.user_id)-1 as total')
                       ->join('message_receiver', 'message_receiver.message_id = message.id')
                       ->join('user', 'message_receiver.user_id = user.id', array('name','image','email'))
                       ->where('message.user_id = ?', $userId)
                       ->where('message.status = ?', 'active')
                       ->group('message.id')
                       ->order('id DESC');
        
        if (isset($options['classroomId']) && $options['classroomId']) {
            $select->where('message.classroom_id = ? OR message.classroom_id IS NULL', $options['classroomId']);
        }

        if (isset($options['receiverId']) && $options['receiverId']) {
            $select->where('message_receiver.user_id = ?', $options['receiverId']);
        }

        if (isset($options['query']) && $options['query']) {
            $select->where('(message.description LIKE(?) OR user.name LIKE(?))', "%" . $options['query'] . "%");
        }

        return $select;
    }
}
