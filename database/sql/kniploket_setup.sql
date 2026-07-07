DROP DATABASE IF EXISTS kniploket_tiko;
CREATE DATABASE kniploket_tiko;
USE kniploket_tiko;

-- Rollen
CREATE TABLE rollen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(50) NOT NULL UNIQUE
);

-- Gebruikers
CREATE TABLE gebruikers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    naam VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    wachtwoord VARCHAR(255) NOT NULL,
    actief BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES rollen(id)
);

-- Klanten
CREATE TABLE klanten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gebruiker_id INT NULL,
    voornaam VARCHAR(50) NOT NULL,
    achternaam VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefoon VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id)
);

-- Adressen
CREATE TABLE adressen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    klant_id INT NOT NULL,
    straatnaam VARCHAR(50) NOT NULL,
    huisnummer INT NOT NULL,
    postcode VARCHAR(10) NOT NULL,
    plaats VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (klant_id) REFERENCES klanten(id) ON DELETE CASCADE
);

-- Medewerkers
CREATE TABLE medewerkers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gebruiker_id INT NULL,
    voornaam VARCHAR(50) NOT NULL,
    achternaam VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefoon VARCHAR(20) NOT NULL,
    functie VARCHAR(50) NOT NULL,
    actief BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id)
);

-- Behandelingen
CREATE TABLE behandelingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL UNIQUE,
    omschrijving TEXT NULL,
    duur INT NOT NULL,
    prijs DECIMAL(10,2) NOT NULL,
    actief BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Afspraken
CREATE TABLE afspraken (
    id INT AUTO_INCREMENT PRIMARY KEY,
    klant_id INT NOT NULL,
    medewerker_id INT NOT NULL,
    datum DATE NOT NULL,
    starttijd TIME NOT NULL,
    eindtijd TIME NOT NULL,
    status ENUM('gepland', 'gewijzigd', 'in behandeling', 'geannuleerd', 'voltooid') NOT NULL DEFAULT 'gepland',
    opmerking TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (klant_id) REFERENCES klanten(id),
    FOREIGN KEY (medewerker_id) REFERENCES medewerkers(id)
);

-- Koppeltabel afspraak en behandeling
CREATE TABLE afspraak_behandeling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    afspraak_id INT NOT NULL,
    behandeling_id INT NOT NULL,
    prijs DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (afspraak_id) REFERENCES afspraken(id) ON DELETE CASCADE,
    FOREIGN KEY (behandeling_id) REFERENCES behandelingen(id)
);

-- ================================================================
-- TESTDATA: Handled by database/seeders/DatabaseSeeder.php
-- ================================================================
-- Wachtwoorden worden NIET hardcoded in SQL
-- In plaats daarvan: TEST_PASSWORD uit .env → Seeder → Bcrypt hash
-- Zie: database/seeders/DatabaseSeeder.php
-- Voordelen:
-- - Geen hardcoded wachtwoorden in versiecontrol
-- - Environment-specifieke configuratie
-- - PHP Bcrypt hashing (BCRYPT_ROUNDS=12)
--
-- Testdata wordt ingeladen via: php artisan db:seed
--
-- Schema is klaar, data wordt via seeder geladen

-- ================================================================
-- STORED PROCEDURE: sp_afspraken_overzicht()
-- ================================================================
-- Doel: Alle afspraken met klant/medewerker/behandeling details
--
-- JOINs:
-- 1. afspraken → klanten (klant naam)
-- 2. afspraken → medewerkers (medewerker naam)
-- 3. afspraken → afspraak_behandeling (junction table!)
-- 4. afspraak_behandeling → behandelingen (behandeling naam)
--
-- Ordering: DESC = NIEUWSTE AFSPRAKEN EERST
--
-- Waarom INNER JOINs?
-- - Voorkomen orphaned/incomplete records
-- - Alleen tonen wat COMPLEET is
-- ================================================================
DELIMITER $$

CREATE PROCEDURE sp_afspraken_overzicht()
BEGIN
    SELECT
        a.id,
        CONCAT(k.voornaam, ' ', k.achternaam) AS klant,
        CONCAT(m.voornaam, ' ', m.achternaam) AS medewerker,
        b.naam AS behandeling,
        a.datum,
        a.starttijd,
        a.eindtijd,
        a.status,
        a.opmerking
    FROM afspraken a
    INNER JOIN klanten k ON a.klant_id = k.id
    INNER JOIN medewerkers m ON a.medewerker_id = m.id
    INNER JOIN afspraak_behandeling ab ON a.id = ab.afspraak_id
    INNER JOIN behandelingen b ON ab.behandeling_id = b.id
    ORDER BY a.id DESC;
END $$

DELIMITER ;

-- ================================================================
-- STORED PROCEDURE: sp_klanten_overzicht()
-- ================================================================
-- Doel: Alle klanten met adresgegevens
--
-- JOIN:
-- - klanten INNER JOIN adressen (alleen klanten MET adres)
--
-- Ordering: DESC = NIEUWSTE KLANTEN EERST
--
-- Waarom INNER JOIN?
-- - Voorkomen klanten zonde adres gegevens
-- - Alleen tonen wat COMPLEET is
-- ================================================================
DELIMITER $$

CREATE PROCEDURE sp_klanten_overzicht()
BEGIN
    SELECT
        k.id,
        k.voornaam,
        k.achternaam,
        k.email,
        k.telefoon,
        a.straatnaam,
        a.huisnummer,
        a.postcode,
        a.plaats
    FROM klanten k
    INNER JOIN adressen a ON k.id = a.klant_id
    ORDER BY k.id DESC;
END $$

DELIMITER ;

-- Controle queries
CALL sp_afspraken_overzicht();
CALL sp_klanten_overzicht();
