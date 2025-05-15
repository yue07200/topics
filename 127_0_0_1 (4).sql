-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2025-05-15 18:37:10
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `project`
--
CREATE DATABASE IF NOT EXISTS `project` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `project`;

-- --------------------------------------------------------

--
-- 資料表結構 `daily_exercise_log`
--

CREATE TABLE `daily_exercise_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exercise_date` date NOT NULL,
  `exercise_type` varchar(100) NOT NULL COMMENT '例如: running, swimming, yoga, weight_training, cycling, walking',
  `duration_minutes` int(11) NOT NULL COMMENT '運動時長（分鐘）',
  `notes` text DEFAULT NULL COMMENT '備註 (可選)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `daily_exercise_log`
--

INSERT INTO `daily_exercise_log` (`id`, `user_id`, `exercise_date`, `exercise_type`, `duration_minutes`, `notes`, `created_at`) VALUES
(1, 1, '2025-05-15', 'cycling', 30, '', '2025-05-15 15:51:34'),
(2, 1, '2025-05-15', 'walking', 60, '', '2025-05-15 16:32:36');

-- --------------------------------------------------------

--
-- 資料表結構 `daily_water_intake`
--

CREATE TABLE `daily_water_intake` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `intake_date` date NOT NULL COMMENT '飲水日期',
  `total_ml` int(11) NOT NULL DEFAULT 0 COMMENT '當日總飲水量 (ml)',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `daily_water_intake`
--

INSERT INTO `daily_water_intake` (`id`, `user_id`, `intake_date`, `total_ml`, `updated_at`) VALUES
(1, 1, '2025-05-15', 2640, '2025-05-15 16:32:23');

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `birthdate` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `allergens` text DEFAULT NULL,
  `diet` varchar(50) DEFAULT NULL,
  `goal` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `fullname`, `birthdate`, `gender`, `allergens`, `diet`, `goal`, `created_at`) VALUES
(1, '1111', '1234@gmail.com', '$2y$10$JQXi1t/ipwQ0C/59BgQE/OZ5q/DtYro3x8DyQW.IJBD8JsU2i0.ry', '陳', '2004-10-18', 'female', '', 'omnivore', 'general-health', '2025-05-04 14:32:01');

-- --------------------------------------------------------

--
-- 資料表結構 `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `achievement_type` varchar(50) NOT NULL COMMENT '例如: daily_water, daily_steps',
  `current_level` int(11) NOT NULL DEFAULT 0,
  `current_streak` int(11) NOT NULL DEFAULT 0 COMMENT '連續打卡天數',
  `last_check_in_date` date DEFAULT NULL COMMENT '上次打卡日期',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_type`, `current_level`, `current_streak`, `last_check_in_date`, `updated_at`) VALUES
(1, 1, 'daily_water', 1, 1, '2025-05-15', '2025-05-15 15:12:58');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `daily_exercise_log`
--
ALTER TABLE `daily_exercise_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`exercise_date`);

--
-- 資料表索引 `daily_water_intake`
--
ALTER TABLE `daily_water_intake`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_date_unique` (`user_id`,`intake_date`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 資料表索引 `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_achievement_unique` (`user_id`,`achievement_type`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `daily_exercise_log`
--
ALTER TABLE `daily_exercise_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `daily_water_intake`
--
ALTER TABLE `daily_water_intake`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `daily_exercise_log`
--
ALTER TABLE `daily_exercise_log`
  ADD CONSTRAINT `daily_exercise_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `daily_water_intake`
--
ALTER TABLE `daily_water_intake`
  ADD CONSTRAINT `daily_water_intake_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `user_achievements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
