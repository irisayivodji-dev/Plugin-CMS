

-- Type pour les rôles
DO $$ BEGIN
    CREATE TYPE user_role AS ENUM ('admin', 'editor', 'author');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Table users
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    firstname VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    role user_role DEFAULT 'author',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index sur l'email pour optimiser les recherches
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Fonction pour mettre à jour automatiquement updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour updated_at
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Admin par défaut (password: admin123)
INSERT INTO users (email, password, firstname, lastname, role) VALUES 
('admin@cms.local', '$2y$10$K3fi0ciDncw/IMsHTQ2dpeqs7CNgsjqaAswVw1aNTa8Y8yDStFJ/C', 'Admin', 'CMS', 'admin')
ON CONFLICT (email) DO NOTHING;