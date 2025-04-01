CREATE DATABASE IF NOT EXISTS `defaultdb`;
USE `defaultdb`;

CREATE TABLE `exercises` (
    `id_exe` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL UNIQUE,
    `description` VARCHAR(500) NOT NULL,
    `category` VARCHAR(10) NOT NULL,
    `likes` INT NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id_exe`)
);

CREATE TABLE `exercise_group` (
    `id_group` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_group`, `id_exe`)
);

CREATE TABLE `exercise_groups` (
    `id_group` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `category` VARCHAR(10) NOT NULL,
    `likes` INT NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id_group`)
);

CREATE TABLE `users` (
    `id_usr` INT NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `username` VARCHAR(20) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(5) NOT NULL DEFAULT 'USER',
    `active` BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id_usr`)
);

CREATE TABLE `likes` (
    `id_usr` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_usr`, `id_exe`)
);

CREATE TABLE `exercisesXuser` (
    `id_usr` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_usr`, `id_exe`)
);

ALTER TABLE `exercise_group`
ADD CONSTRAINT `fk_exercise_group_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises` (`id_exe`);

ALTER TABLE `exercise_group`
ADD CONSTRAINT `fk_exercise_group_id_group` FOREIGN KEY (`id_group`) REFERENCES `exercise_groups` (`id_group`);

ALTER TABLE `exercisesXuser`
ADD CONSTRAINT `fk_exercisesXuser_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises` (`id_exe`);

ALTER TABLE `exercisesXuser`
ADD CONSTRAINT `fk_exercisesXuser_id_usr` FOREIGN KEY (`id_usr`) REFERENCES `users` (`id_usr`);

ALTER TABLE `likes`
ADD CONSTRAINT `fk_likes_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises` (`id_exe`);

ALTER TABLE `likes`
ADD CONSTRAINT `fk_likes_id_usr` FOREIGN KEY (`id_usr`) REFERENCES `users` (`id_usr`);