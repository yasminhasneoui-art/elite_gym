# 📘 Guide d'Utilisation — Élite Gym

Ce document explique comment utiliser l'application Élite Gym, que vous soyez **visiteur**, **membre** ou **administrateur**.

---

## 1. 🌐 Visiteur (site public)

La page d'accueil (`index.html`) est accessible à tous sans connexion.

| Section | Description |
|---|---|
| **Accueil / Hero** | Présentation générale de la salle et offre du mois |
| **Programmes** | Musculation, CrossFit, Boxe, Yoga & Récup, Coach Perso |
| **Tarifs** | 3 formules d'abonnement : Essential (89 DT), Performance (149 DT), Élite (249 DT) |
| **Horaires** | Heures d'ouverture par jour |
| **Témoignages** | Avis de membres |
| **Contact** | Coordonnées et localisation |

Depuis cette page, deux actions sont possibles :
- **« S'inscrire »** → ouvre le formulaire d'inscription (`inscription.html`)
- **« Mon Espace »** → ouvre la page de connexion membre (`login.html`)

---

## 2. 📝 Créer un compte membre

1. Cliquer sur **« S'inscrire »** depuis la page d'accueil.
2. Remplir le formulaire (`inscription.html`) :
   - **Informations personnelles** : prénom, nom, email, téléphone, genre
   - **Identifiants** : nom d'utilisateur et mot de passe (4 caractères minimum)
   - **Programme et abonnement** : choisir une formule (Essential / Performance / Élite) et un mode de paiement (espèces, carte, e-dinar jeune)
   - **Informations santé** (optionnel) : allergies, blessures, conditions médicales
   - **Métriques physiques** (optionnel) : poids et taille → l'application calcule automatiquement :
     - l'**IMC** (Indice de Masse Corporelle)
     - les **calories journalières recommandées**
3. Valider le formulaire.
   - En cas de succès : redirection vers la page de connexion (`login.html`)
   - Le compte est créé avec le statut **« actif »** et une première échéance de paiement **« en attente »** est générée automatiquement.

⚠️ Le nom d'utilisateur et l'email doivent être uniques (un message d'erreur s'affiche en cas de doublon).

---

## 3. 🔑 Connexion membre

1. Aller sur `login.html`.
2. Saisir le **nom d'utilisateur (ou email)** et le **mot de passe**.
3. Cliquer sur **« Se connecter »**.
4. En cas de succès → redirection vers `profil.php` (espace personnel).

---

## 4. 👤 Espace membre (`profil.php`)

Une fois connecté, le membre peut consulter :

- **Profil personnel** : informations générales, coach assigné (spécialités, horaires, jours de disponibilité)
- **Suivi physique** :
  - IMC actuel avec interprétation (insuffisance pondérale, poids normal, surpoids, obésité)
  - Objectif de poids
  - Calories journalières recommandées
- **Séances d'entraînement** :
  - Nombre de séances ce mois / au total
  - Historique des 10 dernières séances (date, type, durée, calories, note)
- **Paiements** :
  - Historique des 5 derniers paiements (montant, date, mode, statut)

Pour se déconnecter, utiliser le lien/bouton qui appelle `logout.php` (retour à la page d'accueil).

---

## 5. 🛠️ Connexion administrateur

1. Aller sur `admin_login.html` (lien « Admin » dans le pied de page du site).
2. Identifiants par défaut (à modifier impérativement après installation) :
   - **Identifiant** : `admin`
   - **Mot de passe** : `admin1234`
3. Cliquer sur **« Accéder au tableau de bord »** → redirection vers `admin.php`.

---

## 6. 📊 Tableau de bord administrateur (`admin.php`)

Le tableau de bord présente :

- **Statistiques globales** :
  - Nombre total de membres
  - Nombre de membres actifs
  - Nombre de séances effectuées ce mois
  - Chiffre d'affaires du mois (paiements au statut « payé »)

- **Liste des membres** : informations complètes, statut, coach assigné, dernier statut de paiement, nombre de séances.

- **Liste des coachs** : spécialités, horaires, jours, places disponibles.

### Actions disponibles sur un membre (via `admin_api.php`)

| Action | Description |
|---|---|
| **Modifier le statut** | Passer un membre à `actif`, `en_attente` ou `suspendu` |
| **Enregistrer un paiement** | Saisir un paiement (montant, date, mode, statut). Si le statut est « payé », la prochaine échéance est calculée et créée automatiquement (+1 mois) |
| **Ajouter une séance** | Enregistrer une nouvelle séance d'entraînement (date, heure, type, durée, calories, note) — met à jour automatiquement les compteurs du membre |
| **Assigner un coach** | Lier ou retirer un coach à un membre |

---

## 7. 🚪 Déconnexion

| Espace | Fichier appelé | Effet |
|---|---|---|
| Membre | `logout.php` | Détruit la session et redirige vers `index.html` |
| Admin | `admin_logout.php` | Supprime uniquement la session admin (la session membre, si présente, reste active) et redirige vers `admin_login.html` |

---

## 8. ❓ Résolution des problèmes courants

| Problème | Cause possible | Solution |
|---|---|---|
| « Identifiant ou mot de passe incorrect » | Identifiants erronés ou compte inexistant | Vérifier la saisie ou créer un compte via l'inscription |
| « Nom d'utilisateur ou email déjà utilisé » | Compte déjà existant | Utiliser un autre nom d'utilisateur/email ou se connecter directement |
| « Erreur de connexion DB » | Base de données non démarrée ou identifiants incorrects dans `config.php` | Vérifier que MySQL est lancé et que `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` sont corrects |
| Redirection vers `login.html` ou `admin_login.html` | Session expirée ou non connecté | Se reconnecter |

---

## 9. 🔒 Bonnes pratiques recommandées avant mise en production

- Changer immédiatement le mot de passe administrateur par défaut.
- Mettre en place le hachage des mots de passe (`password_hash` / `password_verify`) au lieu du stockage en clair.
- Activer HTTPS pour protéger les identifiants transmis.
- Limiter les tentatives de connexion (anti brute-force).
