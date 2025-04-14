CREATE DATABASE app_etudiants;

USE app_etudiants;

CREATE TABLE ecoles (
    id INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL
)engine=InnoDB;

CREATE TABLE classes (
    id INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    ecole_id INT,
    FOREIGN KEY (ecole_id) REFERENCES ecoles(id) ON DELETE CASCADE
)engine=InnoDB;

CREATE TABLE etudiants (
    id INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    classe_id INT,
    mot_de_passe VARCHAR(255) NOT NULL,
    code_connexion CHAR(6) DEFAULT NULL,
    expiration_code_connexion DATETIME DEFAULT NULL,
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE
)engine=InnoDB;

CREATE TABLE fichiers (
    id INT auto_increment PRIMARY KEY,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    date_envoi DATETIME DEFAULT (CURRENT_DATE),
    classe_id INT,
    etudiant_id INT,
    note FLOAT DEFAULT NULL,
    correction_texte TEXT DEFAULT NULL,
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE SET NULL
)engine=InnoDB;

