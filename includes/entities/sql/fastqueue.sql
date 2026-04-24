-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3307
-- Généré le : ven. 24 avr. 2026 à 21:21
-- Version du serveur : 10.4.27-MariaDB
-- Version de PHP : 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `fastqueue`
--

-- --------------------------------------------------------

--
-- Structure de la table `ban`
--

CREATE TABLE `ban` (
  `ban_id` int(11) NOT NULL,
  `ban_source` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ban_trash_count` int(11) NOT NULL DEFAULT 0,
  `ban_time_end` int(11) DEFAULT 0,
  `ban_source_id` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `desk`
--

CREATE TABLE `desk` (
  `desk_id` int(11) NOT NULL,
  `desk_number` tinyint(4) NOT NULL,
  `desk_ip_address` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `desk_op_code` varchar(30) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `desk_last_activity_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `desk`
--

INSERT INTO `desk` (`desk_id`, `desk_number`, `desk_ip_address`, `desk_op_code`, `desk_last_activity_time`) VALUES
(7, 1, '192.168.11.102', '2025', 1777058389);

-- --------------------------------------------------------

--
-- Structure de la table `device`
--

CREATE TABLE `device` (
  `dev_id` int(11) NOT NULL,
  `dev_ip_address` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `dev_desk_number` tinyint(4) NOT NULL,
  `dev_td_code` char(1) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `display_main`
--

CREATE TABLE `display_main` (
  `dm_id` int(11) NOT NULL,
  `dm_ticket` varchar(4) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dm_desk` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='display_main';

--
-- Déchargement des données de la table `display_main`
--

INSERT INTO `display_main` (`dm_id`, `dm_ticket`, `dm_desk`) VALUES
(1, 'S001', '1'),
(2, 'S002', '1'),
(3, 'S003', '1'),
(4, 'S004', '1'),
(5, 'S005', '1'),
(6, 'S006', '1'),
(7, 'S007', '1'),
(8, 'S008', '1'),
(9, 'S009', '1'),
(10, 'S010', '1'),
(11, 'S011', '1'),
(12, 'S012', '1'),
(13, 'S013', '1'),
(14, 'S014', '1'),
(15, 'S015', '1'),
(16, 'S016', '1'),
(17, 'S017', '1'),
(18, 'S018', '1'),
(19, 'S019', '1'),
(20, 'S020', '1'),
(21, 'S021', '1'),
(22, 'S022', '1'),
(23, 'S023', '1'),
(24, 'S024', '1'),
(25, 'S025', '1'),
(26, 'S026', '1'),
(27, 'S027', '1'),
(28, 'S028', '1'),
(29, 'S029', '1'),
(30, 'S030', '1'),
(31, 'S031', '1'),
(32, 'S032', '1'),
(33, 'S033', '1'),
(34, 'S034', '1'),
(35, 'S035', '1'),
(36, 'S036', '1'),
(37, 'S037', '1'),
(38, 'S038', '1'),
(39, 'S039', '1'),
(40, 'S040', '1'),
(41, 'S041', '1'),
(42, 'S042', '1'),
(43, 'S043', '1'),
(44, 'A001', '1'),
(45, 'A002', '1'),
(46, 'S044', '1'),
(47, 'S045', '1'),
(48, 'S046', '1'),
(49, 'S047', '1'),
(50, 'S048', '1'),
(51, 'S049', '1'),
(52, 'S050', '1'),
(53, 'S051', '1'),
(54, 'S052', '1'),
(55, 'S053', '1'),
(56, 'S054', '1'),
(57, 'S055', '1'),
(58, 'S056', '1'),
(59, 'S057', '1'),
(60, 'S058', '1');

-- --------------------------------------------------------

--
-- Structure de la table `office`
--

CREATE TABLE `office` (
  `of_id` int(11) NOT NULL,
  `of_code` varchar(30) NOT NULL,
  `of_name` varchar(100) NOT NULL,
  `of_city` varchar(50) NOT NULL,
  `of_search` varchar(50) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `of_address` varchar(100) DEFAULT '',
  `of_latitude` double DEFAULT NULL,
  `of_longitude` double DEFAULT NULL,
  `of_host` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `operator`
--

CREATE TABLE `operator` (
  `op_id` int(11) NOT NULL,
  `op_code` varchar(30) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `op_name` varchar(80) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `op_surname` varchar(80) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `op_password` char(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `operator`
--

INSERT INTO `operator` (`op_id`, `op_code`, `op_name`, `op_surname`, `op_password`) VALUES
(5, '2025', 'said', 'aissaoui', '1b0e08bc995af3dd6904f89a22b18d7ab824b52a');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `color`, `icon`, `enabled`, `created_at`) VALUES
(1, 'Poste', 'Servizi Postali', NULL, '📮', 1, '2026-04-24 11:44:21'),
(2, 'Cassa', 'Servizi Bancari', NULL, '💳', 1, '2026-04-24 11:44:21'),
(3, 'Informazioni', 'Informazioni Generali', NULL, 'ℹ️', 1, '2026-04-24 11:44:21'),
(4, 'Reclami', 'Reclami e Segnalazioni', NULL, '📋', 1, '2026-04-24 11:44:21');

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('standard','pregnant','disability') NOT NULL,
  `service` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `printed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `name`, `phone`, `status`, `service`, `created_at`, `printed`) VALUES
(2, '202604241924123253', 'Said Aissaoui', '+212653651185', '', 'Accueil', '2026-04-24 18:24:12', 0),


-- --------------------------------------------------------

--
-- Structure de la table `ticket_exec`
--

CREATE TABLE `ticket_exec` (
  `te_id` int(11) NOT NULL,
  `te_code` char(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `te_number` int(11) NOT NULL,
  `te_time_in` int(11) NOT NULL,
  `te_source` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `te_source_id` varchar(50) NOT NULL DEFAULT '',
  `te_time_exec` int(11) NOT NULL,
  `te_op_code` varchar(30) NOT NULL,
  `te_desk_number` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `ticket_in`
--

CREATE TABLE `ticket_in` (
  `ti_id` int(11) NOT NULL,
  `ti_code` char(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ti_number` int(11) NOT NULL,
  `ti_time_in` int(11) NOT NULL,
  `ti_source` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ti_source_id` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `ti_notice_counter` int(11) NOT NULL DEFAULT -1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ticket_stats`
--

CREATE TABLE `ticket_stats` (
  `ts_id` int(11) NOT NULL,
  `ts_code` char(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ts_time_in` int(11) NOT NULL,
  `ts_time_exec` int(11) DEFAULT NULL,
  `ts_time_out` int(11) NOT NULL,
  `ts_source` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ts_op_code` varchar(30) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `ts_is_trash` tinyint(4) NOT NULL,
  `ts_desk_number` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `ticket_stats`
--

INSERT INTO `ticket_stats` (`ts_id`, `ts_code`, `ts_time_in`, `ts_time_exec`, `ts_time_out`, `ts_source`, `ts_op_code`, `ts_is_trash`, `ts_desk_number`) VALUES
(1, 'S', 1775765838, 1775766039, 1775766047, 'totem', '2004', 0, 1),
(2, 'S', 1775766074, 1775766083, 1775766090, 'totem', '2004', 0, 1),
(3, 'S', 1775766095, 1775766101, 1775766155, 'totem', '2004', 0, 1),
(4, 'S', 1775766145, 1775766155, 1775766163, 'totem', '2004', 0, 1),
(5, 'S', 1775766145, 1775766163, 1775766170, 'totem', '2004', 0, 1),
(6, 'S', 1775766146, 1775766170, 1775766182, 'totem', '2004', 0, 1),
(7, 'S', 1775766146, 1775766182, 1775766190, 'totem', '2004', 0, 1),
(8, 'S', 1775766146, 1775766190, 1775766196, 'totem', '2004', 0, 1),
(9, 'S', 1775766146, 1775766196, 1775766213, 'totem', '2004', 0, 1),
(10, 'S', 1775766146, 1775766213, 1775766295, 'totem', '2004', 0, 1),
(11, 'S', 1775766147, 1775766295, 1775766302, 'totem', '2004', 0, 1),
(12, 'S', 1775766147, 1775766302, 1775766645, 'totem', '2004', 0, 1),
(13, 'S', 1775766147, 1775766668, 1775766674, 'totem', '2004', 0, 1),
(14, 'S', 1775766147, 1775766674, 1775766689, 'totem', '2004', 0, 1),
(15, 'S', 1775766216, 1775766689, 1775767589, 'totem', '2004', 0, 1),
(16, 'S', 1775766306, 1775771427, 1775771438, 'totem', '2004', 0, 1),
(17, 'S', 1775766681, 1775771446, 1775771460, 'totem', '2004', 0, 1),
(18, 'S', 1775766683, 1775771460, 1775771519, 'totem', '2004', 0, 1),
(19, 'S', 1775766684, 1775771519, 1775772226, 'totem', '2004', 0, 1),
(20, 'S', 1775766685, 1776263851, 1776263871, 'totem', '2025', 0, 1),
(21, 'S', 1775771378, 1776263871, 1776263900, 'totem', '2025', 0, 1),
(22, 'S', 1775771380, 1776263900, 1776263946, 'totem', '2025', 0, 1),
(23, 'S', 1775771381, 1776263946, 1776264003, 'totem', '2025', 0, 1),
(24, 'S', 1775771434, 1776264003, 1776264249, 'totem', '2025', 0, 1),
(25, 'S', 1775772205, 1776264249, 1776264253, 'totem', '2025', 0, 1),
(26, 'S', 1775772612, 1776264930, 1776264940, 'totem', '2025', 0, 1),
(27, 'S', 1775773021, 1776264940, 1776264954, 'totem', '2025', 0, 1),
(28, 'S', 1775773139, 1776264954, 1776264966, 'totem', '2025', 0, 1),
(29, 'S', 1775773154, 1776265119, 1776265130, 'totem', '2025', 0, 1),
(30, 'S', 1775826910, 1776265130, 1776265139, 'totem', '2025', 0, 1),
(31, 'S', 1775831613, 1776265139, 1776265261, 'totem', '2025', 0, 1),
(32, 'S', 1775832705, 1776265590, 1776265619, 'totem', '2025', 0, 1),
(33, 'S', 1775835542, 1776265620, 1776265624, 'totem', '2025', 0, 1),
(34, 'S', 1776263689, 1776265624, 1776265638, 'totem', '2025', 0, 1),
(35, 'S', 1776263692, 1776265638, 1776265645, 'totem', '2025', 0, 1),
(36, 'S', 1776263693, 1776265645, 1776265708, 'totem', '2025', 0, 1),
(37, 'S', 1776263694, 1776265708, 1776265947, 'totem', '2025', 0, 1),
(38, 'S', 1776264309, 1776272951, 1776272968, 'totem', '2025', 0, 1),
(39, 'S', 1776265609, 1776272968, 1776273097, 'totem', '2025', 0, 1),
(40, 'S', 1776265610, 1776273097, 1776273141, 'totem', '2025', 0, 1),
(41, 'S', 1776265628, 1776273141, 1776273146, 'totem', '2025', 0, 1),
(42, 'S', 1776265795, 1776273146, 1776273153, 'totem', '2025', 0, 1),
(43, 'S', 1776273068, 1776273153, 1776273155, 'totem', '2025', 0, 1),
(44, 'A', 1776273083, 1776273169, 1776273349, 'totem', '2025', 0, 1),
(45, 'A', 1776273085, 1776273546, 1776273559, 'totem', '2025', 0, 1),
(46, 'S', 1776273089, 1776273559, 1776273569, 'totem', '2025', 0, 1),
(47, 'S', 1776273092, 1776275261, 1776275269, 'totem', '2025', 0, 1),
(48, 'S', 1776274220, 1776962782, 1776962808, 'totem', '2025', 0, 1),
(49, 'S', 1776275652, 1776962808, 1776962815, 'totem', '2025', 0, 1),
(50, 'S', 1776277179, 1776962815, 1776962823, 'totem', '2025', 0, 1),
(51, 'S', 1776279852, 1776962823, 1776962835, 'totem', '2025', 0, 1),
(52, 'S', 1776281238, 1776962835, 1776962845, 'totem', '2025', 0, 1),
(53, 'S', 1776288314, 1776962845, 1776962903, 'totem', '2025', 0, 1),
(54, 'S', 1776292632, 1776962903, 1776962908, 'totem', '2025', 0, 1),
(55, 'S', 1776294571, 1776963030, 1776963930, 'totem', '2025', 0, 1),
(56, 'S', 1776295709, 1777027051, 1777027354, 'totem', '2025', 0, 1),
(57, 'S', 1776297316, 1777027376, 1777027911, 'totem', '2025', 0, 1),
(58, 'S', 1776697953, 1777031028, 1777031035, 'totem', '2025', 0, 1),
(59, 'S', 1776697981, 1777031772, 1777031775, 'totem', '2025', 0, 1),
(60, 'S', 1776961973, 1777051510, 1777051515, 'totem', '2025', 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `topical_domain`
--

CREATE TABLE `topical_domain` (
  `td_id` int(11) NOT NULL,
  `td_code` char(1) NOT NULL,
  `td_name` varchar(100) NOT NULL,
  `td_description` varchar(300) DEFAULT NULL,
  `td_active` tinyint(4) NOT NULL,
  `td_icon` tinyint(4) NOT NULL,
  `td_color` tinyint(4) NOT NULL COMMENT 'indexed colors',
  `td_eta` int(11) DEFAULT NULL COMMENT 'seconds',
  `td_next_generated_ticket` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Déchargement des données de la table `topical_domain`
--

INSERT INTO `topical_domain` (`td_id`, `td_code`, `td_name`, `td_description`, `td_active`, `td_icon`, `td_color`, `td_eta`, `td_next_generated_ticket`) VALUES
(1, 'S', 'Accueil', 'Service accueil', 1, 1, 1, 0, 59);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `ban`
--
ALTER TABLE `ban`
  ADD PRIMARY KEY (`ban_id`),
  ADD UNIQUE KEY `ban_source_id` (`ban_source_id`);

--
-- Index pour la table `desk`
--
ALTER TABLE `desk`
  ADD PRIMARY KEY (`desk_id`),
  ADD UNIQUE KEY `desk_number` (`desk_number`,`desk_ip_address`);

--
-- Index pour la table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`dev_id`),
  ADD UNIQUE KEY `dev_ip_address` (`dev_ip_address`);

--
-- Index pour la table `display_main`
--
ALTER TABLE `display_main`
  ADD PRIMARY KEY (`dm_id`);

--
-- Index pour la table `office`
--
ALTER TABLE `office`
  ADD PRIMARY KEY (`of_id`),
  ADD UNIQUE KEY `of_code` (`of_code`),
  ADD KEY `of_search` (`of_search`);

--
-- Index pour la table `operator`
--
ALTER TABLE `operator`
  ADD PRIMARY KEY (`op_id`),
  ADD UNIQUE KEY `op_code` (`op_code`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enabled` (`enabled`);

--
-- Index pour la table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `ticket_exec`
--
ALTER TABLE `ticket_exec`
  ADD PRIMARY KEY (`te_id`);

--
-- Index pour la table `ticket_in`
--
ALTER TABLE `ticket_in`
  ADD PRIMARY KEY (`ti_id`);

--
-- Index pour la table `ticket_stats`
--
ALTER TABLE `ticket_stats`
  ADD PRIMARY KEY (`ts_id`);

--
-- Index pour la table `topical_domain`
--
ALTER TABLE `topical_domain`
  ADD PRIMARY KEY (`td_id`),
  ADD UNIQUE KEY `td_code` (`td_code`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `ban`
--
ALTER TABLE `ban`
  MODIFY `ban_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `desk`
--
ALTER TABLE `desk`
  MODIFY `desk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `device`
--
ALTER TABLE `device`
  MODIFY `dev_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `display_main`
--
ALTER TABLE `display_main`
  MODIFY `dm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `office`
--
ALTER TABLE `office`
  MODIFY `of_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `operator`
--
ALTER TABLE `operator`
  MODIFY `op_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `ticket_exec`
--
ALTER TABLE `ticket_exec`
  MODIFY `te_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `ticket_in`
--
ALTER TABLE `ticket_in`
  MODIFY `ti_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `ticket_stats`
--
ALTER TABLE `ticket_stats`
  MODIFY `ts_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `topical_domain`
--
ALTER TABLE `topical_domain`
  MODIFY `td_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
