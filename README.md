# 🚗🌱 ECORIDE - Plateforme de covoiturage écologique
[![GitHub](https://img.shields.io/badge/GitHub-Repository-black)](https://github.com/OlivierGd/EcoRide)\
[![Docker](https://img.shields.io/badge/Docker-Ready-blue)](https://hub.docker.com/r/oliviergd/ecoride)

# 📖 Description

EcoRide est une application web de covoiturage écologique qui facilite le partage de trajets entre particuliers, avec un focus particulier sur les véhicule électriques et l'impact environnemental.
La plateforme permet aux conducteurs de proposer leurs trajets et aux passagers de les réserver facilement.

>Projet réalisé dans le cadre du titre Professionnel Développeur Web et Web Mobile (DWWM) - Session 2025.

## 🎯 Objectif du projet

* Faciliter la mobilité partagée et réduire l'empreinte carbone
* Promouvoir l'utilisation de véhicules électriques
* Créer une communauté engagée pour l'environnement

## ⭐️ Fonctionnalités
### 👤 Gestion des utilisateurs

* Visiteur : Consulter les trajets proposés
* Passager : Rechercher et réserver des trajets
* Chauffeur : Proposer des trajets, gérer leurs annonces
* Employé : Modérateur de la plateforme
* Administrateur : Gestion comlète de la plateforme

## Fonctionnalités principales

* Rechercher des trajets avec filtres avancés
* Réservation de places avec système de crédits
* Notation et commentaires des trajets
* Mise en avant des véhicules électriques
* Interface responsive
* Système de feedback utilisateur
* Tableau de bord administrateur

## 🏗️ Technologies utilisées

* HTML5
* CSS3
* JavaScript
* PHP
* PostgreSQL
* Bootstrap
* JQuery
* AJAX
* JSON
* Git/Github
* Docker
* Jira

## Prérequis

Avant d'installer le projet, assurez-vous d'avoir : 
* PHP >= 8.3
* PostgreSQL >= 13
* Composer
* Docker
* Docker Compose
* Git

##  👷‍♂️Installation

Image Docker pré-construite

*# Lancer l'application (identique à la production)*

`docker run -d -p 8081:80 --name ecoride oliviergd/ecoride:latest`

*# Accéder à l'application sur*\
`http://localhost:8081/`

*# Arrêter l'application*

`docker stop ecoride && docker rm ecoride`

## 🧑‍💻 Utilisation
### **Pour les chauffeurs**
1. Créer un compte et se connecter
2. Ajouter un véhicule
3. Aller dans "proposer" un trajet
4. Renseigner les détails (départ, arrivée, date, prix, places)
5. Publier l'annonce

### **Pour les passagers**
1. Rechercher un trajet via la page d'accueil
2. Filtrer par lieu, type de véhicule
3. Réserver en utilisant ses crédits
4. Noter le trajet après utilisation

### **Comptes de test**
###   # Administrateur
Email : `admin@ecoride.com`\
Mot de passe : `admin123`

### # Chauffeur
Email : `chauffeur@ecoride.com`\
Mot de passe : `chauffeur123`

### # Passager
Email : `passager@ecoride.com`\
Mot de passe : `passager123`

## Points forts du développement

* Code maintenable : séparation des responsabilités
* Sécurité : Protection contre injection SQL, validation des données
* UI/UX : Interface ergonomique et accessible
* Responsive : Adaptation mobile et desktop

## Evolutions futures

* Application mobile
* Notification en temps réel
* Système de paiement réel
* Tests automatisés
* Geolocalisation
* Chat intégré entre utilisateurs

## Licence

Ce projet est sous licence MIT

## Auteur

Olivier Guissard
* GitHub : @oliviergd
* Projet : EcoRide

## Remerciements

* Centre de formation Studi
* Formateurs du titre Professionnel DWWM
* La communauté open source
