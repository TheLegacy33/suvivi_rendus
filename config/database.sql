DROP DATABASE IF EXISTS app_etudiants;
CREATE DATABASE app_etudiants;

USE app_etudiants;

CREATE TABLE ecole (
    id_ecole INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL
)engine=InnoDB;

INSERT INTO ecole(id_ecole, nom)
VALUES (1, 'KEDGE'), (2, 'ECE'), (3, 'EPSI'), (4, 'EFREI'), (5, 'DORANCO'), (6, 'EIFFEL');

CREATE TABLE classe (
    id_classe INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    id_ecole INT,
    FOREIGN KEY (id_ecole) REFERENCES ecole(id_ecole) ON DELETE CASCADE
)engine=InnoDB;

INSERT INTO classe(id_classe, nom, id_ecole)
VALUES (1, 'EBP / S02', 1), (2, 'EBP / S04', 1), (3, 'EBP / S12', 1), (4, 'SIO1A / DEV', 6);


CREATE TABLE etudiant (
    id_etudiant INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    id_classe INT,
    mot_de_passe VARCHAR(255) NOT NULL,
    code_connexion CHAR(6) DEFAULT NULL,
    expiration_code_connexion DATETIME DEFAULT NULL,
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe) ON DELETE CASCADE
)engine=InnoDB;

CREATE TABLE fichier (
    id_fichier INT auto_increment PRIMARY KEY,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    date_envoi DATETIME DEFAULT (CURRENT_DATE),
    id_classe INT,
    id_etudiant INT,
    note FLOAT DEFAULT NULL,
    correction_texte TEXT DEFAULT NULL,
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe) ON DELETE CASCADE,
    FOREIGN KEY (id_etudiant) REFERENCES etudiant(id_etudiant) ON DELETE SET NULL
)engine=InnoDB;

