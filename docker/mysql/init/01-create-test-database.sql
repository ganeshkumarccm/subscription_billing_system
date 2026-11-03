CREATE DATABASE IF NOT EXISTS billing_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON billing_test.* TO 'billing'@'%';
FLUSH PRIVILEGES;
