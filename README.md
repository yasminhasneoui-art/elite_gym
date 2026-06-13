# 🏋️ Élite Gym — Plateforme de Gestion de Salle de Sport

Application web complète pour une salle de sport fictive (« Élite Gym », Tunis), comprenant un site vitrine public, un espace membre et un tableau de bord d'administration. Développée en **PHP / MySQL** (back-end) avec **HTML, CSS et JavaScript vanilla** (front-end).

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)

---

## 📖 Description

Élite Gym est un projet full-stack qui simule la gestion d'une salle de sport moderne. Il couvre l'ensemble du parcours utilisateur :

- **Site public** : page d'accueil présentant les programmes, tarifs, horaires et témoignages.
- **Inscription & connexion** : formulaire d'inscription complet (informations personnelles, objectifs, métriques physiques, abonnement, mode de paiement) et système d'authentification.
- **Espace membre** : profil personnel avec suivi de l'IMC, calories recommandées, historique des séances, coach assigné et historique des paiements.
- **Espace administrateur** : tableau de bord (statistiques globales, chiffre d'affaires du mois, séances du mois), gestion des membres (statut, paiements, séances, assignation de coach).

## ✨ Fonctionnalités principales

- 🔐 Authentification séparée pour les membres et les administrateurs (sessions PHP)
- 📝 Inscription avec calcul automatique de l'IMC et des besoins caloriques (formule de Harris-Benedict)
- 💳 Gestion des abonnements et suivi des paiements (payé / en attente / retard) avec génération automatique de l'échéance suivante
- 🏃 Suivi des séances d'entraînement (type, durée, calories, notes)
- 👨‍🏫 Système de coachs avec spécialités, horaires et assignation aux membres
- 📊 Tableau de bord administrateur avec statistiques en temps réel
- 🎨 Design moderne et responsive (police Montserrat / DM Sans, thème "or & noir")

## 🛠️ Stack technique

| Catégorie | Technologies |
|---|---|
| Back-end | PHP 8+, PDO (MySQL) |
| Base de données | MySQL / MariaDB |
| Front-end | HTML5, CSS3, JavaScript (vanilla) |
| Sécurité | Sessions PHP, requêtes préparées (PDO) |

> 💡 **Remarque** : dans la version actuelle, tous les fichiers PHP/HTML sont à la racine (chemins relatifs simples comme `config.php`, `images/...`). Si vous adoptez la structure en dossiers ci-dessus, pensez à adapter les chemins de `require_once`, les liens `<a href>`, les actions de formulaire (`fetch('login.php')`, etc.) et les chemins d'images en conséquence.

## 🚀 Installation locale

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-utilisateur/elite-gym.git
   cd elite-gym
   ```

2. **Créer la base de données**
   - Importer `database/elite_gym.sql` dans MySQL/MariaDB (via phpMyAdmin ou en ligne de commande) :
     ```bash
     mysql -u root -p < database/elite_gym.sql
     ```

3. **Configurer la connexion**
   - Modifier `config/config.php` avec vos identifiants de base de données (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`).

4. **Lancer un serveur PHP**
   ```bash
   php -S localhost:8000
   ```

5. **Accéder au site**
   - Site public : `http://localhost:8000/index.html`
   - Espace membre : `http://localhost:8000/login.html`
   - Espace admin : `http://localhost:8000/admin_login.html` (identifiant : `admin`, mot de passe : `admin1234`)

## ⚠️ Notes importantes (à garder en tête pour un usage en production)

- Les mots de passe sont actuellement stockés et comparés **en clair**. Pour un déploiement réel, il faudrait utiliser `password_hash()` / `password_verify()`.
- Les identifiants de la base de données sont codés en dur dans `config.php` — à externaliser via des variables d'environnement (`.env`).
- Ce projet a été conçu à des fins de démonstration / portfolio et n'est pas destiné à un usage en production sans renforcement de la sécurité.


## 📄 Licence

Projet réalisé à des fins éducatives et de démonstration.
