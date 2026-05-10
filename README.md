# 🛡️ Sport RPG : Système de Progression Réel

**Sport RPG** est une application web gamifiée qui transforme l'effort physique réel en progression virtuelle. Inspirée par l'univers des RPG, elle permet aux utilisateurs ("Chasseurs") de monter en niveau, de gagner de l'or et d'évoluer dans un classement mondial en accomplissant des exercices sportifs.

---

## 🚀 Fonctionnalités principales

### 👤 Espace Joueur
- **Système de Classe** : Choix entre Guerrier, Assassin ou Tank influençant les quêtes disponibles.
- **Tableau de Bord** : Suivi des statistiques (XP, Or, Niveau) et visualisation du Rang (E à S).
- **Quêtes Journalières** : 4 missions quotidiennes avec un **système de sécurité anti-fraude** basé sur un timer d'effort.
- **Exploration de Donjons** : Défis complexes par étapes avec des récompenses massives.
- **Temple des Héros** : Classement dynamique filtrable par classe.

### 🛠️ Administration
- **Console de Gestion** : Supervision complète des comptes utilisateurs.
- **Édition de Statistiques** : Modification de l'or, du niveau et recalcul automatique du rang.
- **Sécurité Admin** : Générateur de mots de passe sécurisés et gestion des bannissements.

---

## 🛠️ Stack Technique

- **Backend** : PHP 8.2 (Architecture modulaire, requêtes préparées PDO).
- **Frontend** : HTML5, CSS3 (Tailwind CSS), JavaScript Vanilla.
- **Base de données** : MySQL / MariaDB.
- **Performance** : Système de mise en cache JSON pour le classement.

---

## 📂 Structure du Projet & Sécurité (Note pour le Jury)

Pour ce projet, j'ai appliqué les bonnes pratiques de gestion de versions (Git). Certains répertoires sont volontairement exclus du dépôt distant pour des raisons de sécurité :

### 1. Le dossier `/config` (Confidentialité)
* **Fichier exclu :** `database.php` (contient les identifiants de connexion réels).
* **Solution :** Un fichier `database.php.example` est fourni. Il doit être renommé en `.php` avec les identifiants locaux pour faire fonctionner l'application.

### 2. Le dossier `/cache` (Optimisation)
* **Raison :** Ce dossier stocke des données volatiles (JSON) générées toutes les 15 secondes. 
* **Gestion automatique :** Le script `leaderboard.php` détecte l'absence du dossier et le recrée automatiquement s'il est manquant.

### 3. Le fichier `database.sql`
* Situé à la racine, ce fichier permet d'importer instantanément la structure et les données de test (tables `users`, `quests`, `dungeons`, `user_quests`, `user_dungeons`).

---

## 🔧 Installation

1. Cloner le projet dans votre répertoire `www` (Laragon) ou `htdocs`.
2. Importer le fichier `database.sql` dans votre gestionnaire de base de données.
3. Renommer `config/database.php.example` en `config/database.php` et configurer vos accès.
4. Accéder à l'application via `localhost/votre-dossier`.

---

## 🛡️ Algorithmes & Sécurité

- **Protection SQL** : Utilisation systématique de `PDO::prepare` pour contrer les injections SQL.
- **Validation d'effort** : Comparaison entre le `startTime` (JS) et la validation (PHP) pour empêcher le contournement du timer.
- **Hachage** : Utilisation de `password_hash()` pour la protection des mots de passe utilisateurs.

---
**Développé par Servillat Lucas** - *Candidat BTS SIO*