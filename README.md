# ExamDossiers

## Présentation du projet

**ExamDossiers** est une plateforme web destinée à faciliter le dépôt numérique des dossiers de candidature aux examens nationaux au Bénin.

L'objectif est de permettre aux candidats de déposer leurs dossiers en ligne, de suivre leur traitement et de réduire les déplacements, les files d'attente et les risques liés au dépôt physique des documents.

## Objectifs

* Permettre aux candidats de créer un compte.
* Permettre le dépôt des dossiers en ligne.
* Permettre aux candidats de consulter l'état de leur dossier.
* Permettre aux administrateurs de consulter les dossiers déposés.
* Permettre la validation ou le rejet des dossiers.
* Faciliter la gestion des candidatures.
* Générer le numéro national du candidat après validation.

## Types de candidats

La plateforme prend en compte deux types de candidats :

* **Candidat officiel**
* **Candidat libre**

## Examens concernés

La plateforme peut être utilisée pour la gestion des dossiers liés notamment aux :

* CEP
* BEPC
* BAC
* CAP

## Fonctionnalités principales

### Candidat

* Inscription
* Connexion
* Modification des informations
* Dépôt de dossier
* Consultation du statut du dossier
* Consultation des informations du dossier

### Administrateur

* Connexion à l'espace administrateur
* Consultation des dossiers
* Filtrage des dossiers
* Validation des dossiers
* Rejet des dossiers avec motif
* Gestion des candidats
* Génération du numérotage national
* Consultation des statistiques

## Technologies utilisées

* **HTML5**
* **CSS3**
* **JavaScript**
* **PHP**
* **MySQL**
* **phpMyAdmin**
* **XAMPP**
* **Visual Studio Code**

## Base de données

Le projet utilise une base de données MySQL nommée :

`examdossiers`

La base de données peut être importée à partir du fichier SQL fourni dans le projet.

## Installation

### 1. Installer XAMPP

Installer XAMPP puis démarrer :

* Apache
* MySQL

### 2. Placer le projet

Copier le dossier du projet dans :

`C:\xampp\htdocs\`

### 3. Créer la base de données

Ouvrir phpMyAdmin et créer une base de données appelée :

`examdossiers`

Importer ensuite le fichier SQL fourni avec le projet.

### 4. Configurer la connexion

Vérifier les paramètres de connexion à la base de données dans le fichier PHP prévu pour la connexion.

Paramètres utilisés :

* Hôte : `localhost`
* Base de données : `examdossiers`
* Utilisateur : `root`
* Mot de passe : vide par défaut avec XAMPP

### 5. Lancer le projet

Démarrer Apache et MySQL dans XAMPP puis ouvrir le navigateur.

Adresse locale :

`http://localhost/examd/`

## Structure du projet

```text
ExamDossiers/
│
├── index.php
├── inscription.php
├── connexion.php
├── depot.php
├── admin.php
│
├── css/
│   └── style.css
│
├── js/
│   └── script.js
│
├── images/
│
├── uploads/
│
├── database/
│   └── examdossiers.sql
│
└── README.md
```

> La structure exacte peut varier selon l'organisation finale des fichiers du projet.

## Sécurité

Le projet prévoit notamment :

* Authentification des utilisateurs
* Gestion des rôles candidat/administrateur
* Protection des accès à l'espace administrateur
* Validation des données saisies
* Gestion sécurisée de la connexion à la base de données

##  Auteur

Projet réalisé dans le cadre d'une formation en développement web et mobile.

**Projet : ExamDossiers**

**Année académique : 2025–2026**

## 📄 fin de formation en developpement web et mobile 
