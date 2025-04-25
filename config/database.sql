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
    FOREIGN KEY (id_ecole) REFERENCES ecole(id_ecole)
)engine=InnoDB;

INSERT INTO classe(id_classe, nom, id_ecole)
VALUES (1, 'EBP / S02', 1), (2, 'EBP / S04', 1), (3, 'EBP / S12', 1), (4, 'SIO1A / DEV', 6);


CREATE TABLE etudiant (
    id_etudiant INT auto_increment PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prenom VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    id_classe INT,
    mot_de_passe VARCHAR(255) DEFAULT NULL,
    code_connexion CHAR(6) DEFAULT NULL,
    expiration_code_connexion DATETIME DEFAULT NULL,
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe)
)engine=InnoDB;

INSERT INTO etudiant(nom, prenom, email, id_classe)
VALUES 
('KHATTABI','Romane','romane.khattabi@kedgebs.com',1),
('LAGARDERE','Maxime','maxime.lagardere@kedgebs.com',1),
('LE GOUAREGUER','Lenka','lenka.legouareguer@kedgebs.com',1),
('MAGNIN','Edouard','edouard.magnin@kedgebs.com',1),
('MASIERO','Raffaele','raffaele.masiero@kedgebs.com',1),
('MERIOT','Charles','charles.meriot@kedgebs.com',1),
('MOLIERE','Julien','julien.moliere@kedgebs.com',1),
('MURET','Clément','clement.muret@kedgebs.com',1),
('OLLIER','Baptiste','baptiste.ollier@kedgebs.com',1),
('PAPIN','Gaspard','gaspard.papin@kedgebs.com',1),
('PARNET','Axel','axel.parnet@kedgebs.com',1),
('PERRUCHOT-TRIBOULET','Joseph','joseph.perruchottriboulet@kedgebs.com',1),
('POULIZAC','Julien','julien.poulizac@kedgebs.com',1),
('ROUSSEL','Manon','manon.roussel02@kedgebs.com',1),
('SENAC','Gregoire','gregoire.senac@kedgebs.com',1),
('TALON','Coline','coline.talon@kedgebs.com',1),
('TREUIL','Pablo','pablo.treuil@kedgebs.com',1),
('VERSCHAVE','Paul','paul.verschave@kedgebs.com',1),
('JOSEPH','Lynda','lynda.joseph@kedgebs.com',2),
('KIEFFER','Jade','jade.kieffer@kedgebs.com',2),
('LAGLASSE','Gabin','gabin.laglasse@kedgebs.com',2),
('LE NEVÉ','Gildas','gildas.leneve@kedgebs.com',2),
('LELONG','Camille','camille.lelong@kedgebs.com',2),
('LISSARDY','Léopold','leopold.lissardy@kedgebs.com',2),
('MALARET--SAUZET','Leo-paul','leopaul.malaretsauzet@kedgebs.com',2),
('MATRANGA','Emilie','emilie.matranga@kedgebs.com',2),
('MESSERSCHMIDT','Camille','camille.messerschmidt@kedgebs.com',2),
('MONNIER','Claryce','claryce.monnier@kedgebs.com',2),
('NAJAR','Anaïs','anais.najar@kedgebs.com',2),
('ORABI','Yasmine','yasmine.orabi@kedgebs.com',2),
('PASGRIMAUD','Hugo','hugo.pasgrimaud@kedgebs.com',2),
('PRUVOST','Lise','lise.pruvost@kedgebs.com',2),
('RITTER','Victor','victor.ritter@kedgebs.com',2),
('ROUX','Gabriel','gabriel.roux@kedgebs.com',2),
('SENCIUC','Matei','matei.senciuc@kedgebs.com',2),
('TARZAALI','Sirine','sirine.tarzaali@kedgebs.com',2),
('TROGA','Stanislas','stanislas.troga@kedgebs.com',2),
('VERSEUX','Titouan','titouan.verseux@kedgebs.com',2),
('LACHAUD-NOYERS--LOPES','Constance','constance.lachaudnoyerslopes@kedgebs.com',3),
('LE DROUMAGUET','Alexis','alexis.ledroumaguet@kedgebs.com',3),
('LEFEUVRE','Lucas','lucas.lefeuvre@kedgebs.com',3),
('MABEA','Iria','iria.mabea@kedgebs.com',3),
('MARTIN','Tony','tony.martin@kedgebs.com',3),
('MOTTET','Charlotte','charlotte.mottet@kedgebs.com',3),
('PARIS--CAILLAUD','Elise','elise.pariscaillaud@kedgebs.com',3),
('POULAYE-LILA','Nathan','nathan.poulayelila@kedgebs.com',3),
('RICHARD','Léonie','leonie.richard@kedgebs.com',3),
('ROUABLE','Juliette','juliette.rouable@kedgebs.com',3),
('SAUNIER','Gabriel','gabriel.saunier@kedgebs.com',3),
('SIRIEIX','Noa','noa.sirieix@kedgebs.com',3),
('TONATI','Margaux','margaux.tonati@kedgebs.com',3),
('VERDES','Alexis','alexis.verdes@kedgebs.com',3),
('YBANEZ','Margaux','margaux.ybanez@kedgebs.com',3),
('ZORKOT','Quentin','quentin.zorkot@kedgebs.com',3);

CREATE TABLE evaluation(
	id_evaluation INT auto_increment PRIMARY KEY,
	nom VARCHAR(200) NOT NULL
)engine=InnoDB;

INSERT INTO evaluation(id_evaluation, nom) VALUES (1, 'Projet EXCEL V1'), (2, 'Projet EXCEL V2'), (3, 'ACCESS / BDD');

CREATE TABLE affecter_evaluation(
	id_evaluation INT NOT NULL,
	id_classe INT NOT NULL,
	PRIMARY KEY(id_evaluation, id_classe),
	FOREIGN KEY (id_evaluation) REFERENCES evaluation(id_evaluation),
	FOREIGN KEY (id_classe) REFERENCES classe(id_classe)
)engine=InnoDB;

INSERT INTO affecter_evaluation(id_evaluation, id_classe) VALUES (1, 1), (1, 2), (1, 3), (2, 1), (2, 2), (2, 3), (3, 1), (3, 2), (3, 3);

CREATE TABLE fichier (
    id_fichier INT auto_increment PRIMARY KEY,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    date_envoi DATETIME DEFAULT (CURRENT_DATE),
    id_classe INT NOT NULL,
    id_etudiant INT NOT NULL,
    id_evaluation INT NOT NULL,
    note FLOAT DEFAULT NULL,
    correction_texte TEXT DEFAULT NULL,
    FOREIGN KEY (id_classe) REFERENCES classe(id_classe),
    FOREIGN KEY (id_evaluation) REFERENCES evaluation(id_evaluation),
    FOREIGN KEY (id_etudiant) REFERENCES etudiant(id_etudiant)
)engine=InnoDB;

