CREATE DATABASE sina_portfolio;
USE sina_portfolio;

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(50) NOT NULL,
    content_key VARCHAR(100) NOT NULL,
    content_value TEXT,
    content_type ENUM('text', 'html', 'json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_key (section, content_key)
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    tags JSON,
    project_url VARCHAR(500),
    featured BOOLEAN DEFAULT FALSE,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE tools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    description VARCHAR(255),
    order_index INT DEFAULT 0
);

CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    order_index INT DEFAULT 0
);

-- Insert default admin user (password: admin123)
INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert default content
INSERT INTO content (section, content_key, content_value) VALUES
('hero', 'subtitle', 'Hi, my name is'),
('hero', 'name', 'Sina Tavakoli'),
('hero', 'title', 'I create 3D experiences'),
('hero', 'description', 'Product designer crafting immersive digital experiences that blend creativity with cutting-edge 3D technology'),
('about', 'title', 'About Me'),
('about', 'paragraph1', 'Hello! I\'m Sina, a passionate product designer with over 3 years of experience creating beautiful, functional digital products with immersive 3D elements.'),
('about', 'paragraph2', 'My passion lies in solving complex problems through design and creating experiences that users love. I specialize in UI/UX design, 3D visualization, and creative direction.'),
('contact', 'title', 'Let\'s Work Together'),
('contact', 'subtitle', 'Ready to create something amazing? Let\'s discuss your project'),
('stats', 'projects', '50'),
('stats', 'clients', '30'),
('stats', 'experience', '3'),
('stats', 'awards', '15');

