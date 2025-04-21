CREATE SCHEMA IF NOT EXISTS defaultdb;


CREATE TABLE defaultdb.exercises (
  id_exe int NOT NULL PRIMARY KEY,
  name varchar(30) NOT NULL UNIQUE,
  description varchar(500) NOT NULL,
  category int NOT NULL,
  likes int NOT NULL,
  active tinyint NOT NULL DEFAULT 1
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.exercises (id_exe);
CREATE UNIQUE INDEX defaultdb_name ON defaultdb.exercises (name);
CREATE UNIQUE INDEX defaultdb_idx_exercises_name_unique ON defaultdb.exercises (name);
CREATE INDEX defaultdb_fk_exercises_category ON defaultdb.exercises (category);

CREATE TABLE defaultdb.categories (
  id_cat int NOT NULL PRIMARY KEY,
  name varchar(50) NOT NULL UNIQUE,
  active tinyint NOT NULL DEFAULT 1
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.categories (id_cat);
CREATE UNIQUE INDEX defaultdb_name ON defaultdb.categories (name);

CREATE TABLE defaultdb.users (
  id_usr int NOT NULL PRIMARY KEY,
  email varchar(255) NOT NULL UNIQUE,
  username varchar(20) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  role_id int NOT NULL DEFAULT 3,
  active tinyint NOT NULL DEFAULT 1,
  public tinyint NOT NULL DEFAULT 1,
  token varchar(255),
  date_union datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.users (id_usr);
CREATE UNIQUE INDEX defaultdb_email ON defaultdb.users (email);
CREATE UNIQUE INDEX defaultdb_username ON defaultdb.users (username);
CREATE UNIQUE INDEX defaultdb_idx_users_email_unique ON defaultdb.users (email);
CREATE UNIQUE INDEX defaultdb_idx_users_username_unique ON defaultdb.users (username);
CREATE INDEX defaultdb_fk_users_role ON defaultdb.users (role_id);

CREATE TABLE defaultdb.exercise_group (
  id_exg int NOT NULL PRIMARY KEY,
  id_group int NOT NULL,
  id_exe int NOT NULL
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.exercise_group (id_exg);
CREATE INDEX defaultdb_idx_exercise_group_id_exe ON defaultdb.exercise_group (id_exe);
CREATE INDEX defaultdb_idx_exercise_group_id_group ON defaultdb.exercise_group (id_group);

CREATE TABLE defaultdb.favorite_exercises (
  id_fe int NOT NULL PRIMARY KEY,
  id_usr int NOT NULL,
  id_exe int NOT NULL,
  active tinyint NOT NULL DEFAULT 1
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.favorite_exercises (id_fe);
CREATE INDEX defaultdb_idx_favorite_exercises_id_exe ON defaultdb.favorite_exercises (id_exe);
CREATE INDEX defaultdb_idx_favorite_exercises_id_usr ON defaultdb.favorite_exercises (id_usr);

CREATE TABLE defaultdb.exercise_groups (
  id_group int NOT NULL PRIMARY KEY,
  name varchar(30) NOT NULL,
  description varchar(500) NOT NULL,
  category varchar(10) NOT NULL,
  likes int NOT NULL,
  active tinyint NOT NULL DEFAULT 1,
  public tinyint NOT NULL DEFAULT 1
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.exercise_groups (id_group);

CREATE TABLE defaultdb.roles (
  id_role int NOT NULL PRIMARY KEY,
  name varchar(50) NOT NULL UNIQUE,
  active tinyint NOT NULL DEFAULT 1
);

CREATE UNIQUE INDEX defaultdb_PRIMARY ON defaultdb.roles (id_role);
CREATE UNIQUE INDEX defaultdb_name ON defaultdb.roles (name);

ALTER TABLE defaultdb.exercise_group ADD CONSTRAINT fk_exercise_group_id_exe FOREIGN KEY (id_exe) REFERENCES defaultdb.exercises (id_exe);
ALTER TABLE defaultdb.exercise_group ADD CONSTRAINT fk_exercise_group_id_group FOREIGN KEY (id_group) REFERENCES defaultdb.exercise_groups (id_group);
ALTER TABLE defaultdb.exercises ADD CONSTRAINT fk_exercises_category FOREIGN KEY (category) REFERENCES defaultdb.categories (id_cat);
ALTER TABLE defaultdb.favorite_exercises ADD CONSTRAINT fk_favorite_exercises_id_exe FOREIGN KEY (id_exe) REFERENCES defaultdb.exercises (id_exe);
ALTER TABLE defaultdb.favorite_exercises ADD CONSTRAINT fk_favorite_exercises_id_usr FOREIGN KEY (id_usr) REFERENCES defaultdb.users (id_usr);
ALTER TABLE defaultdb.users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES defaultdb.roles (id_role);


CREATE TRIGGER favorite_exercises_AU3
AFTER UPDATE ON exercises
FOR EACH ROW
UPDATE favorite_exercises 
SET active = FALSE 
WHERE id_exe = NEW.id_exe AND NEW.active = FALSE AND OLD.active = TRUE;


CREATE TRIGGER favorite_exercises_AU4
AFTER UPDATE ON exercises
FOR EACH ROW
UPDATE favorite_exercises 
SET active = TRUE 
WHERE id_exe = NEW.id_exe AND NEW.active = TRUE AND OLD.active = FALSE;


CREATE TRIGGER FAVORITE_EXERCISES_AD 
AFTER DELETE ON favorite_exercises 
FOR EACH ROW
BEGIN
    UPDATE exercises 
    SET likes = likes - 1 
    WHERE id_exe = OLD.id_exe;
END;


CREATE TRIGGER FAVORITE_EXERCISES_AI 
AFTER INSERT ON favorite_exercises 
FOR EACH ROW
BEGIN
    UPDATE exercises 
    SET likes = likes + 1 
    WHERE id_exe = NEW.id_exe;
END;