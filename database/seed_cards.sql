USE onitama_db;
--Το "starting player" οριζει ποιος παει πρωτος αναλογα με το seal που εχει actually η καρτα. (1 = κοκκινος | 2 = μπλε)

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE card_moves;
TRUNCATE TABLE cards;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO cards (id, name, starting_player) VALUES
-- Mirror Pairs
(1,  'Frog',     1),
(2,  'Rabbit',   2),
(3,  'Goose',    2),
(4,  'Rooster',  1),
(5,  'Horse',    1),
(6,  'Ox',       2),
(7,  'Eel',      2),
(8,  'Cobra',    1),
-- Symmetric Cards
(9,  'Dragon',   1),
(10, 'Tiger',    2),
(11, 'Elephant', 1),
(12, 'Crab',     2),
(13, 'Mantis',   1),
(14, 'Monkey',   2),
(15, 'Boar',     1),
(16, 'Crane',    2);

INSERT INTO card_moves (card_id, dx, dy) VALUES
-- 1. Frog
(1, -2, 0), (1, -1, 1), (1, 1, -1),
-- 2. Rabbit
(2, 2, 0), (2, 1, 1), (2, -1, -1),
-- 3. Goose
(3, -1, 0), (3, -1, 1), (3, 1, 0), (3, 1, -1),
-- 4. Rooster
(4, 1, 0), (4, 1, 1), (4, -1, 0), (4, -1, -1),
-- 5. Horse
(5, 0, 1), (5, -1, 0), (5, 0, -1),
-- 6. Ox
(6, 0, 1), (6, 1, 0), (6, 0, -1),
-- 7. Eel
(7, -1, 1), (7, -1, -1), (7, 1, 0),
-- 8. Cobra
(8, 1, 1), (8, 1, -1), (8, -1, 0),
-- 9. Dragon
(9, -2, 1), (9, 2, 1), (9, -1, -1), (9, 1, -1),
-- 10. Tiger
(10, 0, 2), (10, 0, -1),
-- 11. Elephant
(11, -1, 0), (11, 1, 0), (11, -1, 1), (11, 1, 1),
-- 12. Crab
(12, 0, 1), (12, -2, 0), (12, 2, 0),
-- 13. Mantis
(13, -1, 1), (13, 1, 1), (13, 0, -1),
-- 14. Monkey
(14, -1, 1), (14, 1, 1), (14, -1, -1), (14, 1, -1),
-- 15. Boar
(15, 0, 1), (15, -1, 0), (15, 1, 0),
-- 16. Crane
(16, 0, 1), (16, -1, -1), (16, 1, -1);
