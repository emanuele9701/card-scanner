-- RAW SQL QUERY PER PHPMYADMIN
-- Esegui queste query su entrambi i server (locale e remoto)

-- Step 1: Rimuovi il vincolo unique esistente su product_id
ALTER TABLE `market_cards` 
DROP INDEX `market_cards_product_id_unique`;

-- Step 2: Aggiungi un vincolo unique composito su product_id + user_id
ALTER TABLE `market_cards` 
ADD UNIQUE `market_cards_product_user_unique` (`product_id`, `user_id`);

-- NOTA: Questo permette a utenti diversi di avere lo stesso product_id
-- ma impedisce allo stesso utente di avere duplicati dello stesso product_id
