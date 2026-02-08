-- Table médias
CREATE TABLE IF NOT EXISTS media (
    id SERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,  -- 'image', 'video', 'document', 'audio'
    mime_type VARCHAR(100),
    file_size INTEGER,                -- en bytes
    alt_text TEXT,                     -- Pour accessibilité
    title VARCHAR(255),
    description TEXT,
    uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison article_media
CREATE TABLE IF NOT EXISTS article_media (
    article_id INTEGER NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
    media_id INTEGER NOT NULL REFERENCES media(id) ON DELETE CASCADE,
    is_featured BOOLEAN DEFAULT FALSE,  -- Image à la une
    display_order INTEGER DEFAULT 0,   -- Ordre d'affichage
    PRIMARY KEY (article_id, media_id)
);

-- Index pour améliorer les performances
CREATE INDEX idx_media_file_type ON media(file_type);
CREATE INDEX idx_media_uploaded_by ON media(uploaded_by);
CREATE INDEX idx_article_media_article ON article_media(article_id);
CREATE INDEX idx_article_media_media ON article_media(media_id);
CREATE INDEX idx_article_media_featured ON article_media(is_featured);

-- Trigger pour mettre à jour updated_at
CREATE TRIGGER update_media_updated_at
    BEFORE UPDATE ON media
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
