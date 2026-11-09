-- ============================================================================
-- Zadanie 1 (z1) - Logowanie, sesja, rejestracja
-- Schemat bazy danych (przyklad do importu w phpMyAdmin)
-- Baza: TU_NAZWA_BAZY
--
-- UWAGA: przed importem podmien TU_HASLO na wlasne hasla testowe.
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'media/default.png',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

INSERT INTO `users` (`username`, `password`) VALUES
('user1', 'TU_HASLO'),
('user2', 'TU_HASLO');

CREATE TABLE IF NOT EXISTS `goscieportalu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(45) NOT NULL,
  `datetime` datetime DEFAULT current_timestamp(),
  `browser` varchar(255) DEFAULT NULL,
  `screen_width` int DEFAULT NULL,
  `screen_height` int DEFAULT NULL,
  `window_width` int DEFAULT NULL,
  `window_height` int DEFAULT NULL,
  `color_depth` int DEFAULT NULL,
  `cookies_enabled` tinyint(1) DEFAULT NULL,
  `java_enabled` tinyint(1) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;
