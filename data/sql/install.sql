CREATE TABLE IF NOT EXISTS `message` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `classroom_id` bigint(20) DEFAULT NULL,
  `description` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','delete') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `classroom_id` (`classroom_id`),
  KEY `user_id` (`user_id`)
);

CREATE TABLE IF NOT EXISTS `message_receiver` (
  `message_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`,`user_id`),
  KEY `user_id` (`user_id`)
);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `message_receiver`
--
ALTER TABLE `message_receiver`
  ADD CONSTRAINT `message_receiver_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `message` (`id`),
  ADD CONSTRAINT `message_receiver_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`classroom_id`) REFERENCES `classroom` (`id`),
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
