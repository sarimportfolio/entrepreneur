-- ============================================================
--  Entrepreneur Portal - Database Schema
--  Group: F25PROJECTC2D81 | CS619
--  Includes: Send/Receive relationship between USER<->MESSAGE
--            and AGENT<->MESSAGE (teacher's noted missing item)
-- ============================================================

CREATE DATABASE IF NOT EXISTS entrepreneur_portal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE entrepreneur_portal;

-- ------------------------------------------------------------
-- CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    icon        VARCHAR(50),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- USERS (End-Users / Aspiring Entrepreneurs)
-- ------------------------------------------------------------
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    phone        VARCHAR(20),
    profile_pic  VARCHAR(255) DEFAULT 'default.png',
    status       ENUM('active','suspended') DEFAULT 'active',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- AGENTS (Entrepreneurs / Mentors)
-- ------------------------------------------------------------
CREATE TABLE agents (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255),
    phone           VARCHAR(20),
    bio             TEXT,
    expertise       VARCHAR(255),
    specialization  VARCHAR(255),
    experience      INT DEFAULT 0,
    profile_pic     VARCHAR(255) DEFAULT 'default.png',
    linkedin        VARCHAR(255),
    website         VARCHAR(255),
    category_id     INT,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    account_status  ENUM('active','suspended') DEFAULT 'active',
    submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at     DATETIME,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- ADMIN
-- ------------------------------------------------------------
CREATE TABLE admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- BUSINESS CONTENT  (uploaded by Agents, managed by Admin)
-- ------------------------------------------------------------
CREATE TABLE business_content (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    agent_id     INT NOT NULL,
    category_id  INT,
    title        VARCHAR(255) NOT NULL,
    content_type ENUM('business_idea','template','framework','market_research',
                      'startup_plan','partnership','financial','opportunity','other') NOT NULL,
    description  TEXT,
    file_path    VARCHAR(255),
    tags         VARCHAR(255),
    views        INT DEFAULT 0,
    status       ENUM('published','draft') DEFAULT 'published',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id)    REFERENCES agents(id)     ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- MESSAGES
-- Send/Receive relationship:
--   USER  sends  → messages (sender_type='user',  sender_id=user.id)
--   USER  receives ← messages (receiver_type='user',  receiver_id=user.id)
--   AGENT sends  → messages (sender_type='agent', sender_id=agent.id)
--   AGENT receives ← messages (receiver_type='agent', receiver_id=agent.id)
-- ------------------------------------------------------------
CREATE TABLE messages (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    sender_id     INT NOT NULL,
    sender_type   ENUM('user','agent') NOT NULL,
    receiver_id   INT NOT NULL,
    receiver_type ENUM('user','agent') NOT NULL,
    subject       VARCHAR(255),
    body          TEXT NOT NULL,
    is_read       TINYINT(1) DEFAULT 0,
    sent_at       DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for fast inbox/outbox queries
CREATE INDEX idx_msg_sender   ON messages(sender_id,   sender_type);
CREATE INDEX idx_msg_receiver ON messages(receiver_id, receiver_type);

-- ------------------------------------------------------------
-- FORUM TOPICS
-- ------------------------------------------------------------
CREATE TABLE forum_topics (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    author_id    INT NOT NULL,
    author_type  ENUM('user','agent') NOT NULL,
    category_id  INT,
    title        VARCHAR(255) NOT NULL,
    body         TEXT NOT NULL,
    views        INT DEFAULT 0,
    is_pinned    TINYINT(1) DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- FORUM REPLIES
-- ------------------------------------------------------------
CREATE TABLE forum_replies (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    topic_id     INT NOT NULL,
    author_id    INT NOT NULL,
    author_type  ENUM('user','agent') NOT NULL,
    body         TEXT NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- FEEDBACK (User → Agent  AND  Admin → Agent)
-- ------------------------------------------------------------
CREATE TABLE feedback (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    from_id      INT NOT NULL,
    from_type    ENUM('user','admin') NOT NULL,
    agent_id     INT NOT NULL,
    rating       TINYINT CHECK (rating BETWEEN 1 AND 5),
    comment      TEXT,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- SEED DATA
-- ------------------------------------------------------------

-- Default admin (password: admin123)
INSERT INTO admins (name, email, password) VALUES
('Admin', 'admin@portal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Categories
INSERT INTO categories (name, description, icon) VALUES
('Technology',       'Tech startups, software, apps, and digital innovation',      '💻'),
('E-Commerce',       'Online retail, dropshipping, and marketplace businesses',     '🛒'),
('Finance',          'Fintech, investment, banking, and financial services',        '💰'),
('Healthcare',       'MedTech, health services, and wellness businesses',           '🏥'),
('Education',        'EdTech, tutoring, online courses, and learning platforms',   '📚'),
('Agriculture',      'AgriTech, farming, food production, and supply chain',       '🌾'),
('Real Estate',      'Property, construction, and real estate technology',         '🏗️'),
('Marketing',        'Digital marketing, branding, and advertising startups',      '📣'),
('Manufacturing',    'Production, supply chain, and industrial startups',          '🏭'),
('Consulting',       'Business consulting, strategy, and advisory services',       '🤝');

-- Forgot password security questions
ALTER TABLE users  ADD COLUMN IF NOT EXISTS security_q VARCHAR(255) DEFAULT NULL;
ALTER TABLE users  ADD COLUMN IF NOT EXISTS security_a VARCHAR(255) DEFAULT NULL;
ALTER TABLE agents ADD COLUMN IF NOT EXISTS security_q VARCHAR(255) DEFAULT NULL;
ALTER TABLE agents ADD COLUMN IF NOT EXISTS security_a VARCHAR(255) DEFAULT NULL;
