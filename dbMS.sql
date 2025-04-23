-- MySQL database export

START TRANSACTION;

CREATE DATABASE IF NOT EXISTS `defaultdb`;

CREATE TABLE IF NOT EXISTS `defaultdb`.`categories` (
    `id_cat` INT NOT NULL,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_cat`)
);

CREATE UNIQUE INDEX `idx_categories_name_unique`
ON `defaultdb`.`categories` (`name`);

CREATE TABLE IF NOT EXISTS `defaultdb`.`exercises` (
    `id_exe` INT NOT NULL,
    `name` VARCHAR(30) NOT NULL UNIQUE,
    `description` VARCHAR(500) NOT NULL,
    `category` INT NOT NULL,
    `likes` INT NOT NULL,
    `active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_exe`)
);

CREATE UNIQUE INDEX `idx_exercises_name_unique`
ON `defaultdb`.`exercises` (`name`);

CREATE UNIQUE INDEX `idx_exercises_name_unique`
ON `defaultdb`.`exercises` (`name`);

CREATE INDEX `idx_exercises_category`
ON `defaultdb`.`exercises` (`category`);

CREATE TABLE IF NOT EXISTS `defaultdb`.`favorite_exercises` (
    `id_fe` INT NOT NULL,
    `id_usr` INT NOT NULL,
    `id_exe` INT NOT NULL,
    `active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_fe`)
);

CREATE INDEX `idx_favorite_exercises_id_exe`
ON `defaultdb`.`favorite_exercises` (`id_exe`);

CREATE INDEX `idx_favorite_exercises_id_usr`
ON `defaultdb`.`favorite_exercises` (`id_usr`);

CREATE TABLE IF NOT EXISTS `defaultdb`.`exercise_groups` (
    `id_group` INT NOT NULL,
    `name` VARCHAR(30) NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `category` INT NOT NULL,
    `likes` INT NOT NULL,
    `active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_group`)
);

CREATE INDEX `idx_exercise_groups_category`
ON `defaultdb`.`exercise_groups` (`category`);

CREATE TABLE IF NOT EXISTS `defaultdb`.`exercise_group` (
    `id_exg` INT NOT NULL,
    `id_group` INT NOT NULL,
    `id_exe` INT NOT NULL,
    PRIMARY KEY (`id_exg`)
);

CREATE INDEX `idx_exercise_group_id_exe`
ON `defaultdb`.`exercise_group` (`id_exe`);

CREATE INDEX `idx_exercise_group_id_group`
ON `defaultdb`.`exercise_group` (`id_group`);

CREATE TABLE IF NOT EXISTS `defaultdb`.`users` (
    `id_usr` INT NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `username` VARCHAR(20) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL DEFAULT 3,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `public` tinyint NOT NULL DEFAULT 1,
    `token` VARCHAR(255),
    `date_union` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `verification_code` INT,
    PRIMARY KEY (`id_usr`)
);

CREATE UNIQUE INDEX `idx_users_email_unique`
ON `defaultdb`.`users` (`email`);

CREATE UNIQUE INDEX `idx_users_username_unique`
ON `defaultdb`.`users` (`username`);

CREATE UNIQUE INDEX `idx_users_email_unique`
ON `defaultdb`.`users` (`email`);

CREATE UNIQUE INDEX `idx_users_username_unique`
ON `defaultdb`.`users` (`username`);

CREATE INDEX `idx_users_role_id`
ON `defaultdb`.`users` (`role_id`);

CREATE TABLE IF NOT EXISTS `defaultdb`.`roles` (
    `id_role` INT NOT NULL,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_role`)
);

CREATE UNIQUE INDEX `idx_roles_name_unique`
ON `defaultdb`.`roles` (`name`);

-- Foreign key constraints

ALTER TABLE `defaultdb`.`exercise_groups`
ADD CONSTRAINT `fk_exercise_groups_category` FOREIGN KEY(`category`) REFERENCES `defaultdb`.`categories`(`id_cat`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `defaultdb`.`exercise_group`
ADD CONSTRAINT `fk_exercise_group_id_exe` FOREIGN KEY(`id_exe`) REFERENCES `defaultdb`.`exercises`(`id_exe`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `defaultdb`.`exercise_group`
ADD CONSTRAINT `fk_exercise_group_id_group` FOREIGN KEY(`id_group`) REFERENCES `defaultdb`.`exercise_groups`(`id_group`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `defaultdb`.`exercises`
ADD CONSTRAINT `fk_exercises_category` FOREIGN KEY(`category`) REFERENCES `defaultdb`.`categories`(`id_cat`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `defaultdb`.`favorite_exercises`
ADD CONSTRAINT `fk_favorite_exercises_id_exe` FOREIGN KEY(`id_exe`) REFERENCES `defaultdb`.`exercises`(`id_exe`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `defaultdb`.`favorite_exercises`
ADD CONSTRAINT `fk_favorite_exercises_id_usr` FOREIGN KEY(`id_usr`) REFERENCES `defaultdb`.`users`(`id_usr`)
ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `defaultdb`.`users`
ADD CONSTRAINT `fk_users_role_id` FOREIGN KEY(`role_id`) REFERENCES `defaultdb`.`roles`(`id_role`)
ON UPDATE CASCADE ON DELETE RESTRICT;

COMMIT;


CREATE TRIGGER update_exercise_likes
BEFORE UPDATE ON favorite_exercises
FOR EACH ROW
BEGIN
    IF OLD.active = TRUE AND NEW.active = FALSE THEN
        UPDATE exercises 
        SET likes = likes - 1 
        WHERE id_exe = OLD.id_exe;
    ELSEIF OLD.active = FALSE AND NEW.active = TRUE THEN
        UPDATE exercises 
        SET likes = likes + 1 
        WHERE id_exe = NEW.id_exe;
    END IF;
END;

CREATE TRIGGER update_favorite_exercises_active
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.status = 'deleted' AND OLD.status != 'deleted' THEN
        UPDATE favorite_exercises
        SET active = FALSE
        WHERE id_usr = NEW.id_usr;
    ELSEIF NEW.status = 'active' AND OLD.status = 'deleted' THEN
        UPDATE favorite_exercises
        SET active = TRUE
        WHERE id_usr = NEW.id_usr;
    END IF;
END;