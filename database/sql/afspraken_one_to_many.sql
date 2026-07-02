-- Zet afspraken van many-to-many (afspraak_behandeling) om naar one-to-many (afspraken.behandeling_id)
-- Draai dit script op je MySQL database nadat je een backup hebt gemaakt.

START TRANSACTION;

ALTER TABLE afspraken
    ADD COLUMN behandeling_id INT NULL AFTER medewerker_id;

UPDATE afspraken a
INNER JOIN afspraak_behandeling ab ON ab.afspraak_id = a.id
SET a.behandeling_id = ab.behandeling_id;

ALTER TABLE afspraken
    MODIFY COLUMN behandeling_id INT NOT NULL,
    ADD CONSTRAINT fk_afspraken_behandeling
        FOREIGN KEY (behandeling_id) REFERENCES behandelingen(id);

DROP TABLE afspraak_behandeling;

COMMIT;

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_afspraken_overzicht $$
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
        b.prijs AS prijs,
        a.opmerking
    FROM afspraken a
    INNER JOIN klanten k ON a.klant_id = k.id
    INNER JOIN medewerkers m ON a.medewerker_id = m.id
    INNER JOIN behandelingen b ON a.behandeling_id = b.id
    ORDER BY a.datum, a.starttijd;
END $$

DELIMITER ;
