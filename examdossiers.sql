-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 19 août 2026 à 06:31
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `examdossiers`
--

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `dossier_id` int(11) NOT NULL,
  `type_document` varchar(100) NOT NULL,
  `fichier_url` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id`, `dossier_id`, `type_document`, `fichier_url`, `uploaded_at`) VALUES
(1, 1, 'Acte de Naissance Écolier (PDF)', 'uploads/1784224659_cep_naissance_Cahier_des_Charges_ExamDossiers.pdf', '2026-07-16 17:57:39'),
(2, 1, 'Photo d identité Écolier (PDF)', 'uploads/1784224659_cep_photo_Cahier_de_Charges_Professionnel_ExamDossiers.pdf', '2026-07-16 17:57:39');

-- --------------------------------------------------------

--
-- Structure de la table `dossiers`
--

CREATE TABLE `dossiers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `statut` enum('en_attente','valide','rejete') DEFAULT 'en_attente',
  `motif_rejet` text DEFAULT NULL,
  `numero_table` varchar(50) DEFAULT NULL,
  `date_depot` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `dossiers`
--

INSERT INTO `dossiers` (`id`, `user_id`, `examen_id`, `statut`, `motif_rejet`, `numero_table`, `date_depot`) VALUES
(1, 1, 4, 'valide', NULL, NULL, '2026-07-16 17:57:39');

-- --------------------------------------------------------

--
-- Structure de la table `examens`
--

CREATE TABLE `examens` (
  `id` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `annee` int(11) NOT NULL,
  `date_cloture` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `examens`
--

INSERT INTO `examens` (`id`, `libelle`, `annee`, `date_cloture`, `created_at`) VALUES
(1, 'BAC', 2026, '2026-06-30', '2026-06-12 16:30:34'),
(2, 'BEPC', 2026, '2026-06-15', '2026-06-12 16:30:34'),
(3, 'CAP', 2026, '2026-05-30', '2026-06-12 16:30:34'),
(4, 'CEP', 2026, '2026-05-15', '2026-06-12 16:30:34');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('candidat','admin') DEFAULT 'candidat',
  `type_candidat` enum('officiel','libre') DEFAULT 'officiel',
  `etablissement` varchar(150) NOT NULL DEFAULT 'Candidat Libre',
  `departement` varchar(50) NOT NULL DEFAULT 'Littoral',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `telephone`, `password`, `role`, `type_candidat`, `etablissement`, `departement`, `created_at`) VALUES
(1, 'TONOUKOUIN', ' Sena Stéphane', 'candidat@test.bj', '+22990000001', '$2y$10$7zB3T4yREO60yvWj3m9oWeGf.3EonX6pU7G5fN8A2jGZ1v7L7Qv8m', 'candidat', 'officiel', 'CEG Gbegamey', 'Atlantique', '2026-06-12 16:30:34'),
(2, 'Direction', 'Examens', 'admin@dec.bj', '+22900000000', 'azertu', 'admin', 'officiel', 'Bureau National DEC', 'Littoral', '2026-06-12 16:30:34');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dossier_id` (`dossier_id`);

--
-- Index pour la table `dossiers`
--
ALTER TABLE `dossiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `double_inscription_interdite` (`user_id`,`examen_id`),
  ADD KEY `examen_id` (`examen_id`);

--
-- Index pour la table `examens`
--
ALTER TABLE `examens`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `dossiers`
--
ALTER TABLE `dossiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `examens`
--
ALTER TABLE `examens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dossiers`
--
ALTER TABLE `dossiers`
  ADD CONSTRAINT `dossiers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dossiers_ibfk_2` FOREIGN KEY (`examen_id`) REFERENCES `examens` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
