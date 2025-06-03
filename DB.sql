START TRANSACTION;

CREATE DATABASE IF NOT EXISTS `testing`;
USE `testing`;

CREATE TABLE IF NOT EXISTS
  `roles` (
    `role_id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `active` tinyint NOT NULL DEFAULT '1',
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `name` (`name`)
  );

CREATE TABLE
  `users` (
    `user_id` int NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `username` varchar(20) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` int NOT NULL DEFAULT '3',
    `status` varchar(20) NOT NULL DEFAULT 'pending',
    `public` tinyint NOT NULL DEFAULT '1',
    `token` varchar(255) DEFAULT NULL,
    `verification_code` int DEFAULT NULL,
    `date_union` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `description` varchar(500) DEFAULT NULL,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `idx_users_email_unique` (`email`),
    UNIQUE KEY `idx_users_username_unique` (`username`),
    KEY `fk_users_role` (`role`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role`) REFERENCES `roles` (`role_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `categories` (
    `category_id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `active` tinyint NOT NULL DEFAULT '1',
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `name` (`name`)
  );

CREATE TABLE
  `chat_type` (
    `chatType_id` int NOT NULL AUTO_INCREMENT,
    `type` tinyint NOT NULL,
    PRIMARY KEY (`chatType_id`)
  );

CREATE TABLE IF NOT EXISTS
  `chats` (
    `chat_id` int NOT NULL AUTO_INCREMENT,
    `type_id` int NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`chat_id`),
    KEY `fk_chat_type` (`type_id`),
    CONSTRAINT `fk_chat_type` FOREIGN KEY (`type_id`) REFERENCES `chat_type` (`chatType_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `chat_users` (
    `chatUsers_id` int NOT NULL AUTO_INCREMENT,
    `chat_id` int NOT NULL,
    `user_id` int NOT NULL,
    PRIMARY KEY (`chatUsers_id`),
    KEY `fk_chatUsers_chat` (`chat_id`),
    KEY `fk_chatUsers_users` (`user_id`),
    CONSTRAINT `fk_chatUsers_chat` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`chat_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_chatUsers_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `messages` (
    `message_id` int NOT NULL AUTO_INCREMENT,
    `chat_id` int NOT NULL,
    `user_id` int NOT NULL,
    `message` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`message_id`),
    KEY `fk_chat` (`chat_id`),
    KEY `fk_user` (`user_id`),
    CONSTRAINT `fk_chat` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`chat_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `exercises` (
    `exercise_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `name` varchar(30) NOT NULL,
    `description` varchar(500) NOT NULL,
    `category` int NOT NULL,
    `active` tinyint NOT NULL DEFAULT '1',
    PRIMARY KEY (`exercise_id`),
    UNIQUE KEY `idx_exercises_name_unique` (`name`),
    KEY `fk_exercises_category` (`category`),
    KEY `fk_users_exercise` (`user_id`),
    CONSTRAINT `fk_exercises_category` FOREIGN KEY (`category`) REFERENCES `categories` (`category_id`),
    CONSTRAINT `fk_users_exercise` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
  );

CREATE TABLE
  `groups` (
    `group_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `name` varchar(30) NOT NULL,
    `description` varchar(500) NOT NULL,
    `category` int NOT NULL,
    `active` tinyint NOT NULL DEFAULT '1',
    PRIMARY KEY (`group_id`),
    UNIQUE KEY `idx_groups_name_unique` (`name`),
    KEY `FK_CATEGORY` (`category`),
    KEY `FK_USERS_GROUPS` (`user_id`),
    CONSTRAINT `FK_CATEGORY` FOREIGN KEY (`category`) REFERENCES `categories` (`category_id`),
    CONSTRAINT `FK_USERS_GROUPS` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
  );

CREATE TABLE IF NOT EXISTS
  `exercise_groups` (
    `exerciseGroups_id` int NOT NULL AUTO_INCREMENT,
    `group_id` int NOT NULL,
    `exercise_id` int NOT NULL,
    PRIMARY KEY (`exerciseGroups_id`),
    KEY `idx_exercise_group_id_exe` (`exercise_id`),
    KEY `idx_exercise_group_id_group` (`group_id`),
    CONSTRAINT `fk_exercise_group_id_exe` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`exercise_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_exercise_group_id_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `favorites_coaches` (
    `coachesFavorites_id` int NOT NULL AUTO_INCREMENT,
    `coach_id` int NOT NULL,
    `user_id` int NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`coachesFavorites_id`),
    KEY `favorite_coach_user` (`user_id`),
    KEY `favorite_coach_coach` (`coach_id`),
    CONSTRAINT `favorite_coach_coach` FOREIGN KEY (`coach_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `favorite_coach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `favorites_exercises` (
    `exercisesFavorite_id` int NOT NULL AUTO_INCREMENT,
    `exercise_id` int NOT NULL,
    `user_id` int NOT NULL,
    `active` tinyint NOT NULL DEFAULT '1',
    `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`exercisesFavorite_id`),
    KEY `idx_favorite_exercises_id_exe` (`exercise_id`),
    KEY `idx_favorite_exercises_id_usr` (`user_id`),
    CONSTRAINT `fk_favorite_exercises_id_exe_v2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`exercise_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_favorite_exercises_id_usr` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `favorites_groups` (
    `groupsFavorite_id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `group_id` int NOT NULL,
    `active` tinyint NOT NULL DEFAULT '1',
    `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`groupsFavorite_id`),
    KEY `idx_favorite_exercises_id_exe` (`group_id`),
    KEY `idx_favorite_exercises_id_usr` (`user_id`),
    CONSTRAINT `favorite_group_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `favorite_groups_groups` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `likes_coachs` (
    `coachLike_id` int NOT NULL AUTO_INCREMENT,
    `coach_id` int NOT NULL,
    `likes` int NOT NULL DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`coachLike_id`),
    KEY `coachs_likes_coach` (`coach_id`),
    CONSTRAINT `coachs_likes_coach` FOREIGN KEY (`coach_id`) REFERENCES `favorites_coaches` (`coach_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `likes_exercises` (
    `exerciseLike_id` int NOT NULL AUTO_INCREMENT,
    `exercise_id` int NOT NULL,
    `likes` int NOT NULL DEFAULT '0',
    PRIMARY KEY (`exerciseLike_id`),
    KEY `likes_exercises_V2` (`exercise_id`),
    CONSTRAINT `likes_exercises_V2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`exercise_id`) ON DELETE CASCADE ON UPDATE CASCADE
  );

CREATE TABLE IF NOT EXISTS
  `likes_groups` (
    `groupsLike_id` int NOT NULL AUTO_INCREMENT,
    `group_id` int NOT NULL,
    `likes` int NOT NULL DEFAULT '0',
    PRIMARY KEY (`groupsLike_id`),
    KEY `fk_groups_likes` (`group_id`),
    CONSTRAINT `fk_groups_likes` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE RESTRICT ON UPDATE CASCADE
  );

COMMIT;

INSERT INTO roles (name) VALUES
  ('ROLE_ROOT'),
  ('ROLE_ADMIN'),
  ('ROLE_COACH'),
  ('ROLE_USER');

INSERT INTO users (email, username, password, role, status, public) VALUES
  ('root@gmail.com', 'root', 'r00T123+', 1, 'active', 0),
  ('admin@gmail.com', 'admin', '4dmiN123+', 2, 'active', 0);

INSERT INTO categories (name) VALUES
  ('CHEST'),
  ('SHOULDER'),
  ('TRICEPS'),
  ('BACK'),
  ('BICEPS'),
  ('ABDOMINALS'),
  ('FEMORAL'),
  ('QUADRICEPS'),
  ('CALVES')

-- Triggers para gestión de likes
CREATE TRIGGER trg_initialize_likes_counter
AFTER INSERT ON exercises
FOR EACH ROW
BEGIN
    INSERT INTO likes_exercises (exercise_id, likes) 
    VALUES (NEW.exercise_id, 0);
END;

CREATE TRIGGER trg_increment_likes_on_favorite_add
AFTER INSERT ON favorites_exercises
FOR EACH ROW
BEGIN
    UPDATE likes_exercises
    SET likes = likes + 1
    WHERE exercise_id = NEW.exercise_id;
END;

CREATE TRIGGER trg_decrement_likes_on_favorite_remove
AFTER DELETE ON favorites_exercises
FOR EACH ROW
BEGIN
    UPDATE likes_exercises
    SET likes = likes - 1
    WHERE exercise_id = OLD.exercise_id;
END;

-- Triggers para cambios de estado en favorites_exercises
CREATE TRIGGER trg_handle_likes_on_favorite_activation
AFTER UPDATE ON favorites_exercises
FOR EACH ROW
BEGIN
    -- Cuando se activa un favorito
    IF OLD.active = FALSE AND NEW.active = TRUE THEN
        UPDATE likes_exercises
        SET likes = likes + 1
        WHERE exercise_id = OLD.exercise_id;
    
    -- Cuando se desactiva un favorito
    ELSEIF OLD.active = TRUE AND NEW.active = FALSE THEN
        UPDATE likes_exercises
        SET likes = likes - 1
        WHERE exercise_id = OLD.exercise_id;
    END IF;
END;

-- Triggers para sincronización/cambios con usuarios
CREATE TRIGGER trg_sync_favorites_on_user_status_change
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    -- Cuando un usuario es marcado como eliminado
    IF NEW.status = 'deleted' AND OLD.status != 'deleted' THEN
        -- Desactivar todos sus favoritos
        UPDATE favorites_exercises
        SET active = FALSE
        WHERE user_id = NEW.user_id;
        
        -- Actualizar contadores de likes
        UPDATE likes_exercises le
        JOIN favorites_exercises fe ON le.exercise_id = fe.exercise_id
        SET le.likes = le.likes - 1
        WHERE fe.user_id = NEW.user_id AND fe.active = TRUE;
    
    -- Cuando un usuario es reactivado
    ELSEIF NEW.status = 'active' AND OLD.status = 'deleted' THEN
        -- Reactivar favoritos donde el ejercicio esté activo
        UPDATE favorites_exercises fe
        JOIN exercises e ON fe.exercise_id = e.exercise_id
        SET fe.active = TRUE
        WHERE fe.user_id = NEW.user_id AND e.active = TRUE;
        
        -- Actualizar contadores de likes
        UPDATE likes_exercises le
        JOIN favorites_exercises fe ON le.exercise_id = fe.exercise_id
        JOIN exercises e ON fe.exercise_id = e.exercise_id
        SET le.likes = le.likes + 1
        WHERE fe.user_id = NEW.user_id AND fe.active = TRUE AND e.active = TRUE;
    END IF;
END;

-- Triggers para sincronización/cambios con ejercicios
CREATE TRIGGER trg_sync_favorites_on_exercise_status_change
AFTER UPDATE ON exercises
FOR EACH ROW
BEGIN
    -- Cuando un ejercicio es desactivado
    IF NEW.active = FALSE AND OLD.active = TRUE THEN
        UPDATE favorites_exercises
        SET active = FALSE
        WHERE exercise_id = NEW.exercise_id;
        
        -- Actualizar contadores de likes
        UPDATE likes_exercises
        SET likes = likes - (
            SELECT COUNT(*) 
            FROM favorites_exercises 
            WHERE exercise_id = NEW.exercise_id AND active = TRUE
        )
        WHERE exercise_id = NEW.exercise_id;
    
    -- Cuando un ejercicio es reactivado
    ELSEIF NEW.active = TRUE AND OLD.active = FALSE THEN
        UPDATE favorites_exercises fe
        JOIN users u ON fe.user_id = u.user_id
        SET fe.active = TRUE
        WHERE fe.exercise_id = NEW.exercise_id AND u.status = 'active';
        
        -- Actualizar contadores de likes
        UPDATE likes_exercises
        SET likes = likes + (
            SELECT COUNT(*) 
            FROM favorites_exercises fe
            JOIN users u ON fe.user_id = u.user_id
            WHERE fe.exercise_id = NEW.exercise_id 
            AND u.status = 'active'
        )
        WHERE exercise_id = NEW.exercise_id;
    END IF;
END;

-- Triggers para sincronización/cambios con entrenadores
CREATE TRIGGER trg_initialize_likes_coachs_counter
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
  IF NEW.role = 2 AND OLD.role != 2 THEN
    INSERT INTO likes_coachs (coach_id, likes)
    VALUES (NEW.user_id, 0);
  END IF;
END;