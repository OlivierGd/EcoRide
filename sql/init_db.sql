-- init_db.sql
BEGIN;

------------------------------------------------
-- 1. USERS
------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
                                     id_user         SERIAL PRIMARY KEY,
                                     firstname       VARCHAR(50)  NOT NULL,
                                     lastname        VARCHAR(50)  NOT NULL,
                                     email           VARCHAR(120) UNIQUE NOT NULL,
                                     password        VARCHAR(255) NOT NULL,
                                     role            SMALLINT     NOT NULL DEFAULT 0 CHECK (role IN (0,1,2,3)), -- 0 = utilisateur
                                     ranking         NUMERIC(2,1) NOT NULL DEFAULT 5.0,
                                     credits         INT          NOT NULL DEFAULT 20,
                                     status          TEXT         NOT NULL DEFAULT 'actif',
                                     profile_picture TEXT,
                                     created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

------------------------------------------------
-- 2. VEHICULE
------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicule (
                                        id_vehicule     SERIAL PRIMARY KEY,
                                        id_owner        INT REFERENCES users(id_user) ON DELETE CASCADE,
                                        brand           VARCHAR(50) NOT NULL,
                                        model           VARCHAR(50) NOT NULL,
                                        fuel            VARCHAR(20) NOT NULL,
                                        plate           VARCHAR(15) UNIQUE NOT NULL,
                                        seats           SMALLINT    NOT NULL CHECK (seats BETWEEN 1 AND 7),
                                        verified        BOOLEAN     NOT NULL DEFAULT FALSE,
                                        created_at      TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
);

------------------------------------------------
-- 3. TRIPS
------------------------------------------------
CREATE TABLE IF NOT EXISTS trips (
                                     id_trip         SERIAL PRIMARY KEY,
                                     driver_id       INT REFERENCES users(id_user) ON DELETE CASCADE,
                                     vehicule_id     INT REFERENCES vehicule(id_vehicule),
                                     start_city      TEXT NOT NULL,
                                     end_city        TEXT NOT NULL,
                                     start_datetime  TIMESTAMP NOT NULL,
                                     price_per_seat  NUMERIC(6,2) NOT NULL CHECK (price_per_seat >= 0),
                                     seats_total     SMALLINT NOT NULL CHECK (seats_total BETWEEN 1 AND 7),
                                     status          TEXT     NOT NULL DEFAULT 'A venir'  -- A venir · En cours · A valider · Terminé · Annulé
);

------------------------------------------------
-- 4. BOOKINGS
------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
                                        id_booking      SERIAL PRIMARY KEY,
                                        trip_id         INT REFERENCES trips(id_trip) ON DELETE CASCADE,
                                        passenger_id    INT REFERENCES users(id_user) ON DELETE CASCADE,
                                        seats_reserved  SMALLINT NOT NULL CHECK (seats_reserved >= 1),
                                        status          TEXT     NOT NULL DEFAULT 'reservé',  -- reservé · En cours · A valider · Terminé · Annulé
                                        created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

------------------------------------------------
-- 5. PAYMENTS
------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
                                        id_payment      SERIAL PRIMARY KEY,
                                        booking_id      INT UNIQUE REFERENCES bookings(id_booking) ON DELETE CASCADE,
                                        amount          NUMERIC(8,2) NOT NULL,
                                        status          TEXT NOT NULL DEFAULT 'pending',  -- pending · succeeded · failed
                                        created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

------------------------------------------------
-- 6. REVIEWS
------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
                                       id_review       SERIAL PRIMARY KEY,
                                       booking_id      INT UNIQUE REFERENCES bookings(id_booking) ON DELETE CASCADE,
                                       author_id       INT REFERENCES users(id_user) ON DELETE CASCADE,
                                       rating          SMALLINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                                       comment         TEXT,
                                       created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

------------------------------------------------
-- 7. CREDITS HISTORY
------------------------------------------------
CREATE TABLE IF NOT EXISTS credits_history (
                                               id_history      SERIAL PRIMARY KEY,
                                               user_id         INT REFERENCES users(id_user) ON DELETE CASCADE,
                                               delta           INT  NOT NULL, -- + ajout / - dépense
                                               reason          TEXT NOT NULL,
                                               balance_before  INT  NOT NULL,
                                               balance_after   INT  NOT NULL,
                                               created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMIT;

