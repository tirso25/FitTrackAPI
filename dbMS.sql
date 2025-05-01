START TRANSACTION;

CREATE DATABASE IF NOT EXISTS `defaultdb`;
USE `defaultdb`;

CREATE TABLE IF NOT EXISTS `categories` (
  `id_cat` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_cat`),
  UNIQUE KEY `idx_categories_name_unique` (`name`)
);

CREATE TABLE IF NOT EXISTS `roles` (
  `id_role` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `idx_roles_name_unique` (`name`)
);

CREATE TABLE IF NOT EXISTS `users` (
  `id_usr` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `username` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL DEFAULT 3,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `public` TINYINT NOT NULL DEFAULT 1,
  `token` VARCHAR(255),
  `date_union` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verification_code` INT,
  PRIMARY KEY (`id_usr`),
  UNIQUE KEY `idx_users_email_unique` (`email`),
  UNIQUE KEY `idx_users_username_unique` (`username`),
  KEY `idx_users_role_id` (`role_id`),
  CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `exercises` (
  `id_exe` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(30) NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `category` INT NOT NULL,
  `likes` INT NOT NULL,
  `active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_exe`),
  UNIQUE KEY `idx_exercises_name_unique` (`name`),
  KEY `idx_exercises_category` (`category`),
  CONSTRAINT `fk_exercises_category` FOREIGN KEY (`category`) REFERENCES `categories`(`id_cat`) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `exercise_groups` (
  `id_group` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(30) NOT NULL,
  `description` VARCHAR(500) NOT NULL,
  `category` INT NOT NULL,
  `likes` INT NOT NULL,
  `active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_group`),
  KEY `idx_exercise_groups_category` (`category`),
  CONSTRAINT `fk_exercise_groups_category` FOREIGN KEY (`category`) REFERENCES `categories`(`id_cat`) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `exercise_group` (
  `id_exg` INT NOT NULL AUTO_INCREMENT,
  `id_group` INT NOT NULL,
  `id_exe` INT NOT NULL,
  PRIMARY KEY (`id_exg`),
  KEY `idx_exercise_group_id_group` (`id_group`),
  KEY `idx_exercise_group_id_exe` (`id_exe`),
  CONSTRAINT `fk_exercise_group_id_group` FOREIGN KEY (`id_group`) REFERENCES `exercise_groups`(`id_group`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exercise_group_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises`(`id_exe`) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `favorite_exercises` (
  `id_fe` INT NOT NULL AUTO_INCREMENT,
  `id_usr` INT NOT NULL,
  `id_exe` INT NOT NULL,
  `active` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_fe`),
  KEY `idx_favorite_exercises_id_usr` (`id_usr`),
  KEY `idx_favorite_exercises_id_exe` (`id_exe`),
  CONSTRAINT `fk_favorite_exercises_id_usr` FOREIGN KEY (`id_usr`) REFERENCES `users`(`id_usr`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_favorite_exercises_id_exe` FOREIGN KEY (`id_exe`) REFERENCES `exercises`(`id_exe`) ON UPDATE CASCADE ON DELETE RESTRICT
);

COMMIT;

CREATE TRIGGER test_sum
AFTER UPDATE ON favorite_exercises
FOR EACH ROW
BEGIN
	UPDATE exercise_likes
  SET likes = likes + 1
  WHERE exercise_id = OLD.exercise_id
  AND OLD.active = FALSE AND NEW.active = TRUE ;
END;

CREATE TRIGGER test_resta
AFTER UPDATE ON favorite_exercises
FOR EACH ROW
BEGIN
	UPDATE exercise_likes
  SET likes = likes - 1
  WHERE exercise_id = OLD.exercise_id
  AND OLD.active = TRUE AND NEW.active = FALSE ;
END;

CREATE TRIGGER update_favorite_exercises_active_user
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.status = 'deleted' AND OLD.status != 'deleted' THEN
        UPDATE favorite_exercises
        SET active = FALSE
        WHERE user_id = NEW.user_id;
    ELSEIF NEW.status = 'active' AND OLD.status = 'deleted' THEN
        UPDATE favorite_exercises fe
        JOIN exercises e ON fe.exercise_id = e.exercise_id
        SET fe.active = TRUE
        WHERE fe.user_id = NEW.user_id
          AND e.active = TRUE;
    END IF;
END;

CREATE TRIGGER update_favorite_exercises_active_exercise
AFTER UPDATE ON exercises
FOR EACH ROW
BEGIN
    IF NEW.active = FALSE AND OLD.active != FALSE THEN
        UPDATE favorite_exercises
        SET active = FALSE
        WHERE exercise_id = NEW.exercise_id;
    ELSEIF NEW.active = TRUE AND OLD.active != TRUE THEN
        UPDATE favorite_exercises fe
        JOIN users u ON fe.user_id = u.user_id
        SET fe.active = TRUE
        WHERE fe.exercise_id = NEW.exercise_id
          AND u.status = 'active';
    END IF;
END;

CREATE TRIGGER no_modify_role_admin
BEFORE UPDATE ON roles
FOR EACH ROW
BEGIN
	IF OLD.name = 'ROLE_ADMIN' THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = 'The administrator role cannot be changed';
	END IF;
END;

CREATE TRIGGER prevent_admin_deletion
BEFORE DELETE ON users
FOR EACH ROW
BEGIN
    IF OLD.role = 'ROLE_ADMIN' AND CURRENT_USER() != 'root@localhost' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only root can delete users with ROLE_ADMIN';
    END IF;
END;

CREATE TRIGGER set_like
AFTER INSERT ON exercises
FOR EACH ROW
BEGIN
	INSERT INTO exercise_likes (exercise_id, likes) VALUES(NEW.exercise_id, 0);
END;

CREATE TRIGGER less_like
AFTER DELETE ON favorite_exercises
FOR EACH ROW
BEGIN
	UPDATE exercise_likes
  SET likes = likes - 1
  WHERE exercise_id = OLD.exercise_id;
END;

CREATE TRIGGER test_sum2
AFTER INSERT ON favorite_exercises
FOR EACH ROW
BEGIN
	UPDATE exercise_likes
  SET likes = likes + 1
  WHERE exercise_id = NEW.exercise_id;
END;