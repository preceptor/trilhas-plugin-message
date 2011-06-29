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
 * @package    Message_Plugin
 * @copyright  Copyright (C) 2005-2010  Preceptor Educação a Distância Ltda. <http://www.preceptoead.com.br>
 * @license    http://www.gnu.org/licenses/  GNU GPL
 */
class Message_Plugin extends Tri_Plugin_Abstract
{
    protected $_name = "message";
    
    protected function _createDb()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `message` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `classroom_id` bigint(20) DEFAULT NULL,
                  `description` text NOT NULL,
                  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `classroom_id` (`classroom_id`)
                ) ENGINE=InnoDB;

                CREATE TABLE IF NOT EXISTS `message_route` (
                  `message_id` bigint(20) NOT NULL,
                  `sender` bigint(20) NOT NULL,
                  `receiver` bigint(20) NOT NULL,
                  PRIMARY KEY (`message_id`,`sender`,`receiver`),
                  KEY `sender` (`sender`),
                  KEY `receiver` (`receiver`)
                ) ENGINE=InnoDB;

                ALTER TABLE `message`
                  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`classroom_id`) REFERENCES `classroom` (`id`);

                ALTER TABLE `message_route`
                  ADD CONSTRAINT `message_route_ibfk_3` FOREIGN KEY (`receiver`) REFERENCES `user` (`id`),
                  ADD CONSTRAINT `message_route_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `message` (`id`),
                  ADD CONSTRAINT `message_route_ibfk_2` FOREIGN KEY (`sender`) REFERENCES `user` (`id`);";
        
        $this->_getDb()->query($sql);
    }

    public function install()
    {
        $this->_createDb();
    }

    public function activate()
    {
        $this->_addClassroomMenuItem('communication', 'message', 'message/index/index');
        $this->_addAclItem('message/index/index', 'identified');
        $this->_addAclItem('message/index/view', 'identified');
        $this->_addAclItem('message/index/reply', 'identified');
        $this->_addAclItem('message/index/save', 'identified');
        $this->_addAclItem('message/index/delete', 'identified');
    }

    public function desactivate()
    {
        $this->_removeClassroomMenuItem('communication','message');
        $this->_removeAclItem('message/index/index');
        $this->_removeAclItem('message/index/view');
        $this->_removeAclItem('message/index/reply');
        $this->_removeAclItem('message/index/save');
        $this->_removeAclItem('message/index/delete');
    }
}

