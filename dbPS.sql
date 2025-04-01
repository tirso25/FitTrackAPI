CREATE SCHEMA IF NOT EXISTS "public";

CREATE SEQUENCE IF NOT EXISTS exercises_id_exe_seq;
CREATE SEQUENCE IF NOT EXISTS exercise_groups_id_group_seq;
CREATE SEQUENCE IF NOT EXISTS users_id_usr_seq;

CREATE TABLE "public"."exercises" (
    "id_exe" integer NOT NULL DEFAULT nextval('exercises_id_exe_seq'::regclass),
    "name" character varying(30) NOT NULL UNIQUE,
    "description" character varying(500) NOT NULL,
    "category" character varying(10) NOT NULL,
    "likes" integer NOT NULL,
    "active" boolean NOT NULL DEFAULT true,
    PRIMARY KEY ("id_exe")
);


CREATE UNIQUE INDEX "exercises_unique_name_exercise"
ON "public"."exercises" ("name");


CREATE TABLE "public"."exercise_group" (
    "id_group" integer NOT NULL,
    "id_exe" integer NOT NULL,
    PRIMARY KEY ("id_group", "id_exe")
);



CREATE TABLE "public"."exercise_groups" (
    "id_group" integer NOT NULL DEFAULT nextval('exercise_groups_id_group_seq'::regclass),
    "name" character varying(30) NOT NULL,
    "description" character varying(500) NOT NULL,
    "category" character varying(10) NOT NULL,
    "likes" integer NOT NULL,
    "active" boolean NOT NULL DEFAULT true,
    PRIMARY KEY ("id_group")
);



CREATE TABLE "public"."users" (
    "id_usr" integer NOT NULL DEFAULT nextval('users_id_usr_seq'::regclass),
    "email" character varying(255) NOT NULL UNIQUE,
    "username" character varying(20) NOT NULL UNIQUE,
    "password" character varying(255) NOT NULL,
    "role" character varying(5) NOT NULL DEFAULT '''USER''::character varying',
    "active" boolean NOT NULL DEFAULT true,
    PRIMARY KEY ("id_usr")
);


CREATE UNIQUE INDEX "users_unique_username"
ON "public"."users" ("username");

CREATE UNIQUE INDEX "users_unique_email"
ON "public"."users" ("email");


CREATE TABLE "public"."likes" (
    "id_usr" integer NOT NULL,
    "id_exe" integer NOT NULL,
    PRIMARY KEY ("id_usr", "id_exe")
);



CREATE TABLE "public"."exercisesXuser" (
    "id_usr" integer NOT NULL,
    "id_exe" integer NOT NULL,
    PRIMARY KEY ("id_usr", "id_exe")
);



ALTER TABLE "public"."exercise_group"
ADD CONSTRAINT "fk_exercise_group_id_exe_exercises_id_exe" FOREIGN KEY("id_exe") REFERENCES "public"."exercises"("id_exe");

ALTER TABLE "public"."exercise_group"
ADD CONSTRAINT "fk_exercise_group_id_group_exercise_groups_id_group" FOREIGN KEY("id_group") REFERENCES "public"."exercise_groups"("id_group");

ALTER TABLE "public"."exercisesXuser"
ADD CONSTRAINT "fk_exercisesXuser_id_exe_exercises_id_exe" FOREIGN KEY("id_exe") REFERENCES "public"."exercises"("id_exe");

ALTER TABLE "public"."exercisesXuser"
ADD CONSTRAINT "fk_exercisesXuser_id_usr_users_id_usr" FOREIGN KEY("id_usr") REFERENCES "public"."users"("id_usr");

ALTER TABLE "public"."likes"
ADD CONSTRAINT "fk_likes_id_exe_exercises_id_exe" FOREIGN KEY("id_exe") REFERENCES "public"."exercises"("id_exe");

ALTER TABLE "public"."likes"
ADD CONSTRAINT "fk_likes_id_usr_users_id_usr" FOREIGN KEY("id_usr") REFERENCES "public"."users"("id_usr");