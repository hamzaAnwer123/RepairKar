-- RepairKar — Database Schema
-- MySQL 8.0+ / MariaDB 10.2+ (uses standard InnoDB engine for foreign key support)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- USERS — both "user" and "mechanic" roles share this table.
-- A user signs up with EITHER phone OR email (see signup.html's
-- toggle) — exactly one of the two is required, enforced by the
-- CHECK constraint below as well as by api/register.php.
-- =========================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    phone           VARCHAR(20) DEFAULT NULL UNIQUE,
    email           VARCHAR(150) DEFAULT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('user', 'mechanic', 'admin') NOT NULL DEFAULT 'user',
    city            VARCHAR(100) DEFAULT NULL,
    photo_path      VARCHAR(255) DEFAULT NULL,
    failed_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_users_identifier CHECK (phone IS NOT NULL OR email IS NOT NULL),
    INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- MECHANICS — extends a 'mechanic'-role user with shop info
-- =========================================================
CREATE TABLE IF NOT EXISTS mechanics (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    shop_name       VARCHAR(150) NOT NULL,
    category        VARCHAR(100) NOT NULL,
    bio             TEXT DEFAULT NULL,
    address         VARCHAR(255) DEFAULT NULL,
    cnic_doc_path   VARCHAR(255) DEFAULT NULL,
    shop_photo_path VARCHAR(255) DEFAULT NULL,
    verified        TINYINT(1) NOT NULL DEFAULT 0,
    lat             DECIMAL(10, 7) DEFAULT NULL,
    lng             DECIMAL(10, 7) DEFAULT NULL,
    last_active     TIMESTAMP NULL DEFAULT NULL,
    position_at     TIMESTAMP NULL DEFAULT NULL,
    avg_rating      DECIMAL(2, 1) NOT NULL DEFAULT 0.0,
    review_count    INT UNSIGNED NOT NULL DEFAULT 0,
    rejected          TINYINT(1) NOT NULL DEFAULT 0,
    rejection_reason  TEXT DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mechanics_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_mechanics_user (user_id),
    INDEX idx_mechanics_category (category),
    INDEX idx_mechanics_verified (verified),
    INDEX idx_mechanics_location (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- GIGS — services a mechanic offers, Fiverr-style listings
-- =========================================================
CREATE TABLE IF NOT EXISTS gigs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mechanic_id     INT UNSIGNED NOT NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT NOT NULL,
    price_min       DECIMAL(10, 2) NOT NULL,
    price_max       DECIMAL(10, 2) NOT NULL,
    photo_path      VARCHAR(255) DEFAULT NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gigs_mechanic
        FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE CASCADE,
    CONSTRAINT chk_gigs_price CHECK (price_max >= price_min),
    INDEX idx_gigs_mechanic (mechanic_id),
    INDEX idx_gigs_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- BOOKINGS — a user's request for a mechanic's service
-- =========================================================
CREATE TABLE IF NOT EXISTS bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    mechanic_id     INT UNSIGNED NOT NULL,
    gig_id          INT UNSIGNED DEFAULT NULL,
    status          ENUM('pending', 'accepted', 'en_route', 'completed', 'cancelled')
                        NOT NULL DEFAULT 'pending',
    lat             DECIMAL(10, 7) NOT NULL,
    lng             DECIMAL(10, 7) NOT NULL,
    address         VARCHAR(255) NOT NULL,
    scheduled_time  TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_mechanic
        FOREIGN KEY (mechanic_id) REFERENCES mechanics(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_gig
        FOREIGN KEY (gig_id) REFERENCES gigs(id) ON DELETE SET NULL,
    INDEX idx_bookings_user (user_id),
    INDEX idx_bookings_mechanic (mechanic_id),
    INDEX idx_bookings_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- MESSAGES — chat between a user and mechanic on a booking
-- =========================================================
CREATE TABLE IF NOT EXISTS messages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT UNSIGNED NOT NULL,
    sender_id       INT UNSIGNED NOT NULL,
    sender_role     ENUM('user', 'mechanic') NOT NULL,
    message_type    ENUM('text', 'image', 'video', 'document', 'audio', 'location', 'live_location') NOT NULL DEFAULT 'text',
    content         TEXT NOT NULL,
    original_filename VARCHAR(255) DEFAULT NULL,
    file_size       INT UNSIGNED DEFAULT NULL,
    live_location_expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME DEFAULT NULL,
    CONSTRAINT fk_messages_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_messages_booking (booking_id, id),
    INDEX idx_messages_read (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- CALL SIGNALS — short-lived WebRTC negotiation messages
-- =========================================================
CREATE TABLE IF NOT EXISTS call_signals (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,
    signal_type VARCHAR(20) NOT NULL,
    payload     JSON NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_call_signal_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_call_signals (booking_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    caller_id INT UNSIGNED NOT NULL,
    call_type ENUM('voice', 'video') NOT NULL,
    status ENUM('ringing', 'accepted', 'declined', 'missed', 'ended') NOT NULL DEFAULT 'ringing',
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    duration_seconds INT UNSIGNED DEFAULT NULL,
    CONSTRAINT fk_calls_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_calls_caller FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- CONTACT MESSAGES - public support/contact submissions
-- =========================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED DEFAULT NULL,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    subject    VARCHAR(150) NOT NULL,
    message    TEXT NOT NULL,
    status     ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contact_status (status),
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- REVIEWS — one review per completed booking
-- =========================================================
CREATE TABLE IF NOT EXISTS reviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT UNSIGNED NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    comment         TEXT DEFAULT NULL,
    photo_path      VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_reviews_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;