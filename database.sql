-- --------------------------------------------------------
-- Hôte:                         127.0.0.1
-- Version du serveur:           8.4.3 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Listage de la structure de la base pour sport_rpg
CREATE DATABASE IF NOT EXISTS `sport_rpg` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sport_rpg`;

-- Listage de la structure de table sport_rpg. dungeons
CREATE TABLE IF NOT EXISTS `dungeons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `difficulty_rank` char(1) DEFAULT 'E',
  `xp_reward` int DEFAULT '1000',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sport_rpg.dungeons : ~0 rows (environ)
INSERT INTO `dungeons` (`id`, `name`, `difficulty_rank`, `xp_reward`) VALUES
	(1, 'Le Temple de Fer', 'E', 1000);

-- Listage de la structure de table sport_rpg. quests
CREATE TABLE IF NOT EXISTS `quests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `experience` int NOT NULL,
  `class` varchar(20) DEFAULT 'all',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sport_rpg.quests : ~20 rows (environ)
INSERT INTO `quests` (`id`, `name`, `description`, `experience`, `class`) VALUES
	(1, 'Échauffement du Débutant', 'Faire 15 pompes classiques.', 100, 'all'),
	(2, 'Souffle du Chasseur', 'Courir ou marcher rapidement pendant 10 minutes.', 150, 'all'),
	(3, 'Mobilité Articulaire', 'Faire 5 minutes d\'étirements complets.', 80, 'all'),
	(4, 'Shadow Boxing', '3 minutes de combat dans le vide (cardio).', 120, 'all'),
	(5, 'Burpees Explosifs', 'Faire 10 burpees.', 200, 'all'),
	(6, 'Presse Militaire', 'Faire 20 pompes diamant.', 200, 'warrior'),
	(7, 'Puissance de Frappe', 'Faire 30 squats sautés.', 250, 'warrior'),
	(8, 'Bras de Fer', 'Faire 15 tractions (ou tirage avec un sac lourd).', 300, 'warrior'),
	(9, 'Marche de l\'Ogre', 'Faire 20 fentes marchées avec un poids.', 220, 'warrior'),
	(10, 'Impact au Sol', 'Faire 40 mountain climbers rapides.', 180, 'warrior'),
	(11, 'Pas de l\'Ombre', 'Faire 50 montées de genoux rapides.', 180, 'assassin'),
	(12, 'Gainage Invisible', 'Maintenir la planche (gainage) pendant 1 minute 30.', 220, 'assassin'),
	(13, 'Saut de Toit', 'Faire 20 sauts groupés (genoux à la poitrine).', 240, 'assassin'),
	(14, 'Équilibre Précis', 'Faire 15 pompes claquées (ou explosives).', 280, 'assassin'),
	(15, 'Vitesse de Réaction', 'Faire 30 fentes sautées alternées.', 250, 'assassin'),
	(16, 'Rempart d\'Acier', 'Maintenir la chaise contre un mur pendant 2 minutes.', 250, 'tank'),
	(17, 'Solidité du Noyau', 'Faire 40 relevés de jambes (abdominaux).', 200, 'tank'),
	(18, 'Carapace de Tortue', 'Faire 25 pompes larges (mains très écartées).', 220, 'tank'),
	(19, 'Endurance de Plomb', 'Faire 50 squats classiques sans s\'arrêter.', 300, 'tank'),
	(20, 'Poids Mort', 'Porter un objet lourd (10kg+) et marcher 5 minutes.', 260, 'tank');

-- Listage de la structure de table sport_rpg. users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `class` enum('warrior','assassin','tank') DEFAULT NULL,
  `level` int DEFAULT '1',
  `experience` int DEFAULT '0',
  `gold` int DEFAULT '0',
  `rank` varchar(5) DEFAULT 'E',
  `title` varchar(50) DEFAULT 'Apprenti Chasseur',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` varchar(20) DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sport_rpg.users : ~3 rows (environ)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `class`, `level`, `experience`, `gold`, `rank`, `title`, `created_at`, `role`) VALUES
	(1, 'admin', 'servillatlucas79210@gmail.com', '$2y$10$3DXQdYRaS4hKOGU3e7F2AOFQSPYsKZzxRY1tsRw53LM3jKai3fSC6', 'warrior', 999, 100880, 1000, 'S', 'Apprenti Chasseur', '2026-05-08 12:53:55', 'admin'),
	(4, 'utilisateur', 'utilisateur@gmail.com', '$2y$10$9fEVt2S80VtZbRD6YFlGeO18G3mgrHNcXbgqpmRHidfTbHxklQZcK', 'assassin', 1, 0, 0, 'E', 'Apprenti Chasseur', '2026-05-10 09:50:52', 'user');

-- Listage de la structure de table sport_rpg. user_dungeons
CREATE TABLE IF NOT EXISTS `user_dungeons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `dungeon_id` int NOT NULL,
  `completed_at` date NOT NULL,
  `dungeon_rank` varchar(5) DEFAULT 'E',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_dungeons_ibfk_2` (`dungeon_id`),
  CONSTRAINT `user_dungeons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_dungeons_ibfk_2` FOREIGN KEY (`dungeon_id`) REFERENCES `dungeons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sport_rpg.user_dungeons : ~0 rows (environ)

-- Listage de la structure de table sport_rpg. user_quests
CREATE TABLE IF NOT EXISTS `user_quests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `quest_id` int NOT NULL,
  `completed_at` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `user_quests_ibfk_2` (`quest_id`),
  CONSTRAINT `user_quests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_quests_ibfk_2` FOREIGN KEY (`quest_id`) REFERENCES `quests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table sport_rpg.user_quests : ~2 rows (environ)
INSERT INTO `user_quests` (`id`, `user_id`, `quest_id`, `completed_at`) VALUES
	(5, 1, 1, '2026-05-08'),
	(6, 1, 1, '2026-05-09');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
