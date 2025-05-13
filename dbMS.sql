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

-- 2. Triggers para gestión de likes
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

-- 3. Triggers para cambios de estado en favorites_exercises
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

-- 4. Triggers para sincronización con usuarios
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

-- 5. Triggers para sincronización con ejercicios
CREATE TRIGGER trg_sync_favorites_on_exercise_status_change
AFTER UPDATE ON exercises
FOR EACH ROW
BEGIN
    -- Cuando un ejercicio es desactivado
    IF NEW.active = FALSE AND OLD.active = TRUE THEN
        -- Desactivar todos sus favoritos
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
        -- Reactivar favoritos de usuarios activos
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