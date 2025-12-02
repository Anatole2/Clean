-- Création de la base de test si elle n'existe pas
CREATE DATABASE IF NOT EXISTS sharedParkingTest;

-- On donne tous les droits à ton user habituel sur cette nouvelle base
-- (Remplace 'parking_user' par la valeur de ton ${MYSQL_USER} si tu le connais, 
--  ou utilise '%' pour autoriser l'accès depuis n'importe où dans le réseau docker)
GRANT ALL PRIVILEGES ON sharedParkingTest.* TO 'user'@'%';

FLUSH PRIVILEGES;
