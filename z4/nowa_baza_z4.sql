-- ============================================================================
-- Zadanie 4 (z4) - Monitorowanie usług w sieciach IP
-- Zrzut struktury bazy danych + dane poczatkowe (import do phpMyAdmin)
-- Baza: TU_NAZWA_BAZY (InfinityFree)
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- Tabela uzytkownikow (templatka z1/z2 + kolumna worker z z4)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'media/default.png',
  `created_at` datetime DEFAULT current_timestamp(),
  `worker` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- ----------------------------------------------------------------------------
-- Tabela gosci portalu (templatka z3)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `goscieportalu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(45) NOT NULL,
  `datetime` datetime DEFAULT current_timestamp(),
  `browser` varchar(255) DEFAULT NULL,
  `screen_width` int(11) DEFAULT NULL,
  `screen_height` int(11) DEFAULT NULL,
  `window_width` int(11) DEFAULT NULL,
  `window_height` int(11) DEFAULT NULL,
  `color_depth` int(11) DEFAULT NULL,
  `cookies_enabled` tinyint(1) DEFAULT NULL,
  `java_enabled` tinyint(1) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- ----------------------------------------------------------------------------
-- Tabela domeny - monitorowane hosty/uslugi
--   id           - klucz glowny
--   host         - nazwa domenowa lub adres IP
--   port         - numer portu (usluga)
--   user_id      - klucz obcy -> users.id (wlasciciel wpisu)
--   worker_id    - klucz obcy -> users.id (pracownik odpowiedzialny)
--   errors       - liczba kolejnych nieudanych prob (biezaca seria)
--   failure      - data i godzina pierwszego braku lacznosci w biezacej serii
--   total_errors - laczna liczba nieudanych prob od poczatku monitorowania
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `domeny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(255) NOT NULL,
  `port` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `errors` int(11) NOT NULL DEFAULT 0,
  `failure` datetime DEFAULT NULL,
  `total_errors` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `worker_id` (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_polish_ci;

-- ----------------------------------------------------------------------------
-- Konta: administrator (admin/TU_HASLO) oraz konta testowe (user1, user2).
-- Hasla moga byc jawne - skrypt logowania obsluguje zarowno password_verify(),
-- jak i porownanie jawne (przydatne dla kont demonstracyjnych).
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `users` (`username`, `password`, `avatar`, `worker`) VALUES
  ('admin', 'TU_HASLO', 'media/default.png', 1),
  ('user1', 'TU_HASLO', 'media/default.png', 1),
  ('user2', 'TU_HASLO', 'media/default.png', 1);

-- ----------------------------------------------------------------------------
-- Przykladowe hosty do monitorowania (przypisane do user1).
--   192.0.2.1 to adres z puli TEST-NET-1 (RFC 5737) - celowo nieosiagalny,
--   sluzy do zademonstrowania wykrywania awarii.
-- ----------------------------------------------------------------------------
INSERT INTO `domeny` (`host`, `port`, `user_id`)
SELECT 'fb.com', 80, `id` FROM `users` WHERE `username` = 'user1'
UNION ALL SELECT 'youtube.com', 80, `id` FROM `users` WHERE `username` = 'user1'
UNION ALL SELECT 'pbs.edu.pl', 80, `id` FROM `users` WHERE `username` = 'user1'
UNION ALL SELECT '192.0.2.1', 80, `id` FROM `users` WHERE `username` = 'user1';
