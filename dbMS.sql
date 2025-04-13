START TRANSACTION;

DROP TABLE IF EXISTS `defaultdb`.`favorite_exercises`;
DROP TABLE IF EXISTS `defaultdb`.`exercise_group`;
DROP TABLE IF EXISTS `defaultdb`.`likes`;
DROP TABLE IF EXISTS `defaultdb`.`exercises`;
DROP TABLE IF EXISTS `defaultdb`.`exercise_groups`;
DROP TABLE IF EXISTS `defaultdb`.`users`;

CREATE DATABASE IF NOT EXISTS `defaultdb`;
USE `defaultdb`;

CREATE TABLE IF NOT EXISTS `exercise_groups` (
    `id_group` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `category` VARCHAR(10) NOT NULL,
    `likes` INT NOT NULL,
    `active` TINYINT NOT NULL DEFAULT 1,
    `public` TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_group`)
);

CREATE TABLE IF NOT EXISTS `likes` (
    `id_like` INT NOT NULL AUTO_INCREMENT,
    `id_usr` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_like`)
);

CREATE INDEX `idx_likes_id_exe`
ON `likes` (`id_exe`);

CREATE INDEX `idx_likes_id_usr`
ON `likes` (`id_usr`);

CREATE TABLE IF NOT EXISTS `exercises` (
    `id_exe` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL UNIQUE,
    `description` VARCHAR(500) NOT NULL,
    `category` VARCHAR(10) NOT NULL,
    `likes` INT NOT NULL,
    `active` TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_exe`)
);

CREATE UNIQUE INDEX `idx_exercises_name_unique`
ON `exercises` (`name`);

CREATE TABLE IF NOT EXISTS `users` (
    `id_usr` INT NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `username` VARCHAR(20) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(255) NOT NULL DEFAULT 'ROLE_USER',
    `active` TINYINT NOT NULL DEFAULT 1,
    `token` VARCHAR(255),
    `date_union` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_usr`)
);

CREATE UNIQUE INDEX `idx_users_email_unique`
ON `users` (`email`);

CREATE UNIQUE INDEX `idx_users_username_unique`
ON `users` (`username`);

CREATE TABLE IF NOT EXISTS `exercise_group` (
    `id_exg` INT NOT NULL AUTO_INCREMENT,
    `id_group` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_exg`)
);

CREATE INDEX `idx_exercise_group_id_exe`
ON `exercise_group` (`id_exe`);

CREATE INDEX `idx_exercise_group_id_group`
ON `exercise_group` (`id_group`);

CREATE TABLE IF NOT EXISTS `favorite_exercises` (
    `id_fe` INT NOT NULL AUTO_INCREMENT,
    `id_usr` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_fe`)
);

CREATE INDEX `idx_favorite_exercises_id_exe`
ON `favorite_exercises` (`id_exe`);

CREATE INDEX `idx_favorite_exercises_id_usr`
ON `favorite_exercises` (`id_usr`);

ALTER TABLE `exercise_group`
ADD CONSTRAINT `fk_exercise_group_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises`(`id_exe`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `exercise_group`
ADD CONSTRAINT `fk_exercise_group_id_group` FOREIGN KEY (`id_group`) REFERENCES `exercise_groups`(`id_group`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `favorite_exercises`
ADD CONSTRAINT `fk_favorite_exercises_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises`(`id_exe`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `favorite_exercises`
ADD CONSTRAINT `fk_favorite_exercises_id_usr` FOREIGN KEY (`id_usr`) REFERENCES `users`(`id_usr`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `likes`
ADD CONSTRAINT `fk_likes_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises`(`id_exe`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `likes`
ADD CONSTRAINT `fk_likes_id_usr` FOREIGN KEY (`id_usr`) REFERENCES `users`(`id_usr`)
ON UPDATE CASCADE ON DELETE RESTRICT;

COMMIT;
