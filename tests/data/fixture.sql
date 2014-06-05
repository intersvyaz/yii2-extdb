/**
* This is the database schema for testing Sqlite support of Yii DAO and Active Record.
* The database setup in config.php is required to perform then relevant tests:
*/

DROP TABLE IF EXISTS "profile";

CREATE TABLE "profile" (
  id INTEGER NOT NULL,
  description varchar(128) NOT NULL,
  PRIMARY KEY (id)
);

INSERT INTO "profile" (description) VALUES ('profile customer 1');
INSERT INTO "profile" (description) VALUES ('profile customer 3');