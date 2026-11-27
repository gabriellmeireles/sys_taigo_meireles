-- Banco usado pelos testes automatizados do Laravel (phpunit.xml).
-- Executado só na criação do volume; em volumes existentes, rode scripts/create-test-db.sh.
CREATE DATABASE IF NOT EXISTS taigo_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON taigo_test.* TO 'taigo'@'%';
FLUSH PRIVILEGES;
