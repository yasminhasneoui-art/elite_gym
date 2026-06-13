-- ============================================
--  ÉLITE GYM — Base de données complète v3
-- ============================================
CREATE DATABASE IF NOT EXISTS elite_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elite_gym;

-- --------------------------------------------
--  Table : admins
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(80)  NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  nom         VARCHAR(150) NOT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Admin par défaut : admin / Admin@2026
INSERT INTO admins (username, mot_de_passe, nom) VALUES
('admin', 'admin1234', 'Administrateur Élite Gym');
-- Mot de passe : admin1234

-- --------------------------------------------
--  Table : coachs
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS coachs (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  prenom        VARCHAR(100) NOT NULL,
  nom           VARCHAR(100) NOT NULL,
  specialites   VARCHAR(255) NOT NULL,
  horaire_debut TIME        NOT NULL,
  horaire_fin   TIME        NOT NULL,
  jours         VARCHAR(100) NOT NULL,
  places_dispo  INT         NOT NULL DEFAULT 10
);

-- --------------------------------------------
--  Table : membres (clients)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS membres (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  username          VARCHAR(80)  NOT NULL UNIQUE,
  mot_de_passe      VARCHAR(255) NOT NULL,
  prenom            VARCHAR(100) NOT NULL,
  nom               VARCHAR(100) NOT NULL,
  email             VARCHAR(150) NOT NULL UNIQUE,
  tel               VARCHAR(30)  NOT NULL,
  genre             ENUM('Homme','Femme') NOT NULL,
  sante_type        ENUM('aucun','oui') DEFAULT 'aucun',
  sante_detail      TEXT,
  programmes        VARCHAR(255) NOT NULL,
  abonnement        VARCHAR(50)  NOT NULL,
  message           TEXT,
  coach_id          INT          DEFAULT NULL,
  date_debut        DATE         DEFAULT NULL,
  prochain_paiement DATE         DEFAULT NULL,
  statut            ENUM('en_attente','actif','suspendu') DEFAULT 'actif',
  -- Métriques physiques
  poids             DECIMAL(5,2) DEFAULT NULL,
  taille            DECIMAL(5,2) DEFAULT NULL,
  imc               DECIMAL(5,2) DEFAULT NULL,
  calories_jour     INT          DEFAULT NULL,
  objectif_poids    DECIMAL(5,2) DEFAULT NULL,
  -- Séances
  nb_seances_total  INT          DEFAULT 0,
  nb_seances_mois   INT          DEFAULT 0,
  derniere_seance   DATE         DEFAULT NULL,
  created_at        DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (coach_id) REFERENCES coachs(id) ON DELETE SET NULL
);

-- --------------------------------------------
--  Table : seances (historique check-in)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS seances (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  membre_id   INT NOT NULL,
  date_seance DATE NOT NULL,
  heure       TIME NOT NULL,
  duree_min   INT  DEFAULT 60,
  type        VARCHAR(100) DEFAULT 'Libre',
  calories    INT  DEFAULT NULL,
  note        TEXT,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
);

-- --------------------------------------------
--  Table : paiements (avec mode de paiement)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS paiements (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  membre_id     INT NOT NULL,
  montant       DECIMAL(8,2) NOT NULL,
  date_paiement DATE NOT NULL,
  mode_paiement ENUM('espèces','carte','e-dinar_jeune') NOT NULL DEFAULT 'espèces',
  statut        ENUM('payé','en_attente','retard') DEFAULT 'payé',
  note          TEXT,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (membre_id) REFERENCES membres(id) ON DELETE CASCADE
);

-- --------------------------------------------
--  Données initiales : 4 coachs
-- --------------------------------------------
INSERT INTO coachs (prenom, nom, specialites, horaire_debut, horaire_fin, jours, places_dispo) VALUES
('Karim',   'Mansouri',  'Musculation, CrossFit',          '06:00', '14:00', 'Lun, Mar, Mer, Jeu, Ven', 8),
('Sonia',   'Belhadj',   'Yoga & Récupération, Cardio',    '08:00', '16:00', 'Lun, Mer, Ven, Sam',       6),
('Yassine', 'Trabelsi',  'Boxe, CrossFit, Coaching Perso', '14:00', '22:00', 'Mar, Mer, Jeu, Ven, Sam',  5),
('Nadia',   'Gharbi',    'Coaching Personnel, Musculation', '07:00', '15:00', 'Lun, Mar, Jeu, Ven',       7);
