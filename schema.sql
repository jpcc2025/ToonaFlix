-- Run this once in phpMyAdmin (http://localhost/phpmyadmin) -> toonaflix_db -> SQL tab
CREATE DATABASE IF NOT EXISTS toonaflix_db CHARACTER SET utf8mb4;
USE toonaflix_db;

CREATE TABLE IF NOT EXISTS users (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  email    VARCHAR(150) NOT NULL UNIQUE,
  name     VARCHAR(100) NOT NULL,
  username VARCHAR(50)  NOT NULL UNIQUE,
  age      TINYINT UNSIGNED NOT NULL DEFAULT 18,
  gender   CHAR(1) NOT NULL DEFAULT 'M',
  password VARCHAR(255) NOT NULL
);

-- Needed if your users table already existed: password hashes are 60+ characters
ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;

-- Replaces java.util.prefs from SettingsFrame
CREATE TABLE IF NOT EXISTS user_settings (
  username        VARCHAR(50) PRIMARY KEY,
  theme           VARCHAR(40) NOT NULL DEFAULT 'Midnight Purple',
  default_category VARCHAR(10) NOT NULL DEFAULT 'ALL',
  show_thumbnails TINYINT(1) NOT NULL DEFAULT 1,
  confirm_logout  TINYINT(1) NOT NULL DEFAULT 1,
  plan            VARCHAR(10) NOT NULL DEFAULT 'none'
);

-- Replaces ~/.toonaflix/history/<user>.txt
CREATE TABLE IF NOT EXISTS history (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  username  VARCHAR(50)  NOT NULL,
  category  VARCHAR(20)  NOT NULL,
  title     VARCHAR(150) NOT NULL,
  viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (username, viewed_at)
);
