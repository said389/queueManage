-- --------------------------------------------------------
-- Table structure for table `tickets`
-- Used by the "Espace Client" kiosk interface
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8_bin NOT NULL,
  `phone` varchar(20) COLLATE utf8_bin NOT NULL,
  `state` varchar(30) COLLATE utf8_bin NOT NULL DEFAULT 'standard',
  `service` varchar(100) COLLATE utf8_bin NOT NULL,
  `language` varchar(5) COLLATE utf8_bin NOT NULL DEFAULT 'fr',
  `ticket_number` int(11) NOT NULL DEFAULT '0',
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_service_created_at` (`service`(20), `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;
