-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Apr 13, 2026 alle 18:22
-- Versione del server: 10.4.27-MariaDB
-- Versione PHP: 8.5.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `serietv_streaming`
--
DROP DATABASE IF EXISTS `serietv_streaming`;
CREATE DATABASE IF NOT EXISTS `serietv_streaming` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `serietv_streaming`;

DELIMITER $$
--
-- Procedure
--
DROP PROCEDURE IF EXISTS `add_favorite`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_favorite` (IN `p_uid` INT UNSIGNED, IN `p_pid` VARCHAR(100), IN `p_ptitle` VARCHAR(100))   BEGIN
    INSERT IGNORE INTO user_watchlist (user_id, external_post_id, post_title) 
    VALUES (p_uid, p_pid, p_ptitle);
    
    -- Ritorna 1 se è stata inserita una riga, 0 se esisteva già
    SELECT ROW_COUNT() > 0 AS success;
END$$

DROP PROCEDURE IF EXISTS `check_favorite`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_favorite` (IN `p_uid` INT(10) UNSIGNED, IN `p_pid` VARCHAR(100))  SQL SECURITY INVOKER BEGIN
    SELECT IF(COUNT(*) > 0, 1, 0) AS is_present 
    FROM user_watchlist 
    WHERE user_id = p_uid 
      AND external_post_id = p_pid;
END$$

DROP PROCEDURE IF EXISTS `create_verification_token`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `create_verification_token` (IN `p_user_id` INT UNSIGNED, IN `p_token_hash` VARCHAR(255), IN `p_expires_at` DATETIME)  SQL SECURITY INVOKER BEGIN
    -- elimina eventuale token esistente (1 per utente)
    DELETE FROM verification_tokens WHERE user_id = p_user_id;

    INSERT INTO verification_tokens (
        user_id,
        token,
        expires_at
    )
    VALUES (
        p_user_id,
        p_token_hash,
        p_expires_at
    );

END$$

DROP PROCEDURE IF EXISTS `get_favorites`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_favorites` (IN `p_uid` INT(10) UNSIGNED)  SQL SECURITY INVOKER BEGIN

SELECT external_post_id AS post_id,
	post_title
FROM user_watchlist 
WHERE user_id = p_uid;

END$$

DROP PROCEDURE IF EXISTS `remove_favorite`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `remove_favorite` (IN `p_uid` INT UNSIGNED, IN `p_pid` VARCHAR(100))   BEGIN
    DELETE FROM user_watchlist 
    WHERE user_id = p_uid AND external_post_id = p_pid;
    
    -- Ritorna 1 se la riga esisteva ed è stata eliminata, 0 altrimenti
    SELECT ROW_COUNT() > 0 AS success;
END$$

DROP PROCEDURE IF EXISTS `settings_get`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `settings_get` (IN `p_key` VARCHAR(50))  SQL SECURITY INVOKER BEGIN
    SELECT 
        setting_key,
        setting_value,
        updated_at
    FROM settings
    WHERE setting_key = p_key
    LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `settings_set`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `settings_set` (IN `p_key` VARCHAR(50), IN `p_value` TEXT)  SQL SECURITY INVOKER BEGIN
    DECLARE v_exists INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_exists
    FROM settings
    WHERE setting_key = p_key;

    IF v_exists = 0 THEN
    
        INSERT INTO settings (setting_key, setting_value)
        VALUES (p_key, p_value);

        SELECT 
            TRUE AS success,
            'inserted' AS action,
            p_key AS setting_key,
            p_value AS setting_value;

    ELSE
    
        UPDATE settings
        SET setting_value = p_value,
            updated_at = CURRENT_TIMESTAMP
        WHERE setting_key = p_key;

        SELECT 
            TRUE AS success,
            'updated' AS action,
            p_key AS setting_key,
            p_value AS setting_value;

    END IF;

END$$

DROP PROCEDURE IF EXISTS `update_last_login`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_last_login` (IN `p_user_id` INT)  SQL SECURITY INVOKER BEGIN
    -- Aggiorna solo last_login e forza updated_at a restare invariato
    UPDATE users 
    SET last_login = NOW(), 
        updated_at = updated_at
    WHERE id = p_user_id;
END$$

DROP PROCEDURE IF EXISTS `user_delete`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `user_delete` (IN `p_user_id` INT)  SQL SECURITY INVOKER BEGIN

DECLARE EXIT HANDLER FOR SQLEXCEPTION
BEGIN
    ROLLBACK;
END;

START TRANSACTION;

INSERT INTO users_deleted (id, full_name, email, password_hash, email_verified, role, last_login, created_at, updated_at)
SELECT id, full_name, email, password_hash, email_verified, role, last_login, created_at, updated_at
FROM users
WHERE id = p_user_id;

DELETE FROM users
WHERE id = p_user_id;

COMMIT;

END$$

DROP PROCEDURE IF EXISTS `user_get`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `user_get` (IN `p_id` INT)  SQL SECURITY INVOKER BEGIN

    SELECT
        id,
        full_name,
        email,
        password_hash,
        email_verified,
        role,
        last_login,
        created_at,
        updated_at
    FROM users
    WHERE id = p_id
    LIMIT 1;

END$$

DROP PROCEDURE IF EXISTS `user_login`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `user_login` (IN `p_email` VARCHAR(255))  SQL SECURITY INVOKER BEGIN

    SELECT
        id,
        full_name,
        password_hash,
        email_verified
    FROM users
    WHERE email = p_email
    LIMIT 1;

END$$

DROP PROCEDURE IF EXISTS `user_register`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `user_register` (IN `p_name` VARCHAR(150), IN `p_email` VARCHAR(255), IN `p_password_hash` VARCHAR(255))  SQL SECURITY INVOKER BEGIN

    IF EXISTS (
        SELECT 1 FROM users WHERE email = p_email
    ) THEN

        SELECT FALSE AS success, 'EMAIL_EXISTS' AS error;

    ELSE

        INSERT INTO users (
            full_name,
            email,
            password_hash,
            email_verified
        )
        VALUES (
            p_name,
            p_email,
            p_password_hash,
            0
        );

        SELECT TRUE AS success, LAST_INSERT_ID() AS user_id;

    END IF;

END$$

DROP PROCEDURE IF EXISTS `verify_email_token`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `verify_email_token` (IN `p_token_hash` VARCHAR(255))   BEGIN
    DECLARE v_user_id INT;

    SELECT user_id
    INTO v_user_id
    FROM verification_tokens
    WHERE token = p_token_hash
      AND expires_at > NOW()
    LIMIT 1;

    IF v_user_id IS NOT NULL THEN

        UPDATE users
        SET email_verified = 1
        WHERE id = v_user_id;

        DELETE FROM verification_tokens
        WHERE user_id = v_user_id;

        SELECT TRUE AS success;

    ELSE
        SELECT FALSE AS success;
    END IF;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struttura della tabella `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Svuota la tabella prima dell'inserimento `settings`
--

TRUNCATE TABLE `settings`;
--
-- Dump dei dati per la tabella `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'streaming_domain', 'eurostreamings.xyz', '2026-03-25 09:45:06');

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `role` enum('user','admin') DEFAULT 'user',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Svuota la tabella prima dell'inserimento `users`
--

TRUNCATE TABLE `users`;
--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `email_verified`, `role`, `last_login`, `created_at`, `updated_at`) VALUES
(4, 'Thomas', 'thommy.merly@gmail.com', '$2y$12$v7KzSv/F.JyRTNMzlBHsvORbttMfESt9jnnGFeG1HLRcZh//KBWAG', 1, 'user', '2026-03-25 12:34:45', '2026-03-23 09:08:53', '2026-03-23 10:20:49');

-- --------------------------------------------------------

--
-- Struttura della tabella `users_deleted`
--

DROP TABLE IF EXISTS `users_deleted`;
CREATE TABLE IF NOT EXISTS `users_deleted` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `role` enum('user','admin') DEFAULT 'user',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Svuota la tabella prima dell'inserimento `users_deleted`
--

TRUNCATE TABLE `users_deleted`;
-- --------------------------------------------------------

--
-- Struttura della tabella `user_watchlist`
--

DROP TABLE IF EXISTS `user_watchlist`;
CREATE TABLE IF NOT EXISTS `user_watchlist` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `external_post_id` varchar(100) NOT NULL,
  `post_title` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_post` (`user_id`,`external_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Svuota la tabella prima dell'inserimento `user_watchlist`
--

TRUNCATE TABLE `user_watchlist`;
--
-- Dump dei dati per la tabella `user_watchlist`
--

INSERT INTO `user_watchlist` (`id`, `user_id`, `external_post_id`, `post_title`) VALUES
(7, 4, 'post-98845', 'Heartbreak High'),
(8, 4, 'post-122888', 'Tom e Lola'),
(9, 4, 'post-124265', 'Daredevil: Rinascita');

-- --------------------------------------------------------

--
-- Struttura della tabella `verification_tokens`
--

DROP TABLE IF EXISTS `verification_tokens`;
CREATE TABLE IF NOT EXISTS `verification_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Svuota la tabella prima dell'inserimento `verification_tokens`
--

TRUNCATE TABLE `verification_tokens`;
--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `user_watchlist`
--
ALTER TABLE `user_watchlist`
  ADD CONSTRAINT `fk_watchlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD CONSTRAINT `fk_user_verification` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
