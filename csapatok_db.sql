-- phpMyAdmin SQL Dump
-- version 5.2.2-1.fc41
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 25, 2025 at 08:33 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 8.3.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `csapatok_db`
--
CREATE DATABASE IF NOT EXISTS `csapatok_db` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci;
USE `csapatok_db`;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `match_date` datetime DEFAULT NULL,
  `match_code` varchar(50) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `spectators` int(11) DEFAULT NULL,
  `referee1_name` varchar(100) DEFAULT NULL,
  `referee1_code` varchar(50) DEFAULT NULL,
  `referee2_name` varchar(100) DEFAULT NULL,
  `referee2_code` varchar(50) DEFAULT NULL,
  `match_supervisor` varchar(100) DEFAULT NULL,
  `match_supervisor_code` varchar(50) DEFAULT NULL,
  `home_score_half` int(11) DEFAULT NULL,
  `away_score_half` int(11) DEFAULT NULL,
  `home_score_final` int(11) DEFAULT NULL,
  `away_score_final` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `score_home` int(11) DEFAULT NULL,
  `score_away` int(11) DEFAULT NULL,
  `referees` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `tournament_id`, `round_id`, `home_team_id`, `away_team_id`, `match_date`, `match_code`, `venue`, `spectators`, `referee1_name`, `referee1_code`, `referee2_name`, `referee2_code`, `match_supervisor`, `match_supervisor_code`, `home_score_half`, `away_score_half`, `home_score_final`, `away_score_final`, `created_at`, `updated_at`, `score_home`, `score_away`, `referees`) VALUES
(1, 1, 1, 1, 1, '2025-03-25 10:22:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-25 08:21:47', '2025-03-25 08:21:47', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `match_protocols`
--

CREATE TABLE `match_protocols` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `home_team_score` int(11) DEFAULT NULL,
  `away_team_score` int(11) DEFAULT NULL,
  `home_team_yellow_cards` int(11) DEFAULT 0,
  `away_team_yellow_cards` int(11) DEFAULT 0,
  `home_team_red_cards` int(11) DEFAULT 0,
  `away_team_red_cards` int(11) DEFAULT 0,
  `home_team_goalscorers` text DEFAULT NULL,
  `away_team_goalscorers` text DEFAULT NULL,
  `home_team_substitutions` text DEFAULT NULL,
  `away_team_substitutions` text DEFAULT NULL,
  `home_team_players` text DEFAULT NULL,
  `away_team_players` text DEFAULT NULL,
  `referee_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `match_results`
--

CREATE TABLE `match_results` (
  `match_id` int(11) NOT NULL,
  `result_home` int(11) DEFAULT NULL,
  `result_away` int(11) DEFAULT NULL,
  `tournament_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rounds`
--

CREATE TABLE `rounds` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tournament_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rounds`
--

INSERT INTO `rounds` (`id`, `name`, `tournament_id`) VALUES
(1, '1.fordulo', 1);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tournament_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `tournament_id`) VALUES
(1, 'cspat1', 1),
(2, 'csapat2', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `organization` varchar(100) DEFAULT 'MKSZ',
  `sport_type` varchar(50) DEFAULT 'Teremkézilabda',
  `gender` enum('férfi','női') DEFAULT 'férfi',
  `age_group` varchar(50) DEFAULT 'Felnőtt',
  `competition_type` varchar(50) DEFAULT 'Bajnokság',
  `season` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `name`, `organization`, `sport_type`, `gender`, `age_group`, `competition_type`, `season`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 'fostostorna', 'MKSZ', 'Teremkézilabda', 'férfi', 'Felnőtt', 'Bajnokság', NULL, '1222-12-12', '1222-12-12', '2025-03-25 08:17:02', '2025-03-25 08:17:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`),
  ADD KEY `round_id` (`round_id`),
  ADD KEY `home_team_id` (`home_team_id`),
  ADD KEY `away_team_id` (`away_team_id`);

--
-- Indexes for table `match_protocols`
--
ALTER TABLE `match_protocols`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_match_protocols_match` (`match_id`),
  ADD KEY `fk_match_protocols_tournament` (`tournament_id`);

--
-- Indexes for table `match_results`
--
ALTER TABLE `match_results`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `fk_match_results_tournament` (`tournament_id`);

--
-- Indexes for table `rounds`
--
ALTER TABLE `rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rounds_tournament` (`tournament_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teams_tournament` (`tournament_id`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `match_protocols`
--
ALTER TABLE `match_protocols`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rounds`
--
ALTER TABLE `rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
