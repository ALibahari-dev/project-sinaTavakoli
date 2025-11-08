<?php
require_once 'inc/database.php';
require_once 'inc/functions.php';

// Track visitors
try {
    $db = Database::getInstance();
    $db->insert('visitors', [
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'page_visited' => $_SERVER['REQUEST_URI'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'visit_date' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    // Silently fail to not break the site
}

// Get data from database
 $heroContent = [];
 $aboutContent = [];
 $contactContent = [];
 $stats = [];
 $projects = [];
 $tools = [];
 $skills = [];

try {
    // Get hero content
    $heroData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'hero'");
    foreach ($heroData as $item) {
        $heroContent[$item['content_key']] = $item['content_value'];
    }
    
    // Get about content
    $aboutData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'about'");
    foreach ($aboutData as $item) {
        $aboutContent[$item['content_key']] = $item['content_value'];
    }
    
    // Get contact content
    $contactData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'contact'");
    foreach ($contactData as $item) {
        $contactContent[$item['content_key']] = $item['content_value'];
    }
    
    // Get stats
    $statsData = $db->fetchAll("SELECT content_key, content_value FROM content WHERE section = 'stats'");
    foreach ($statsData as $item) {
        $stats[$item['content_key']] = $item['content_value'];
    }
    
    // Get projects
    $projects = $db->fetchAll("SELECT * FROM projects ORDER BY order_index ASC, created_at DESC");
    
    // Get tools
    $tools = $db->fetchAll("SELECT * FROM tools ORDER BY order_index ASC");
    
    // Get skills
    $skills = $db->fetchAll("SELECT * FROM skills ORDER BY order_index ASC");
    
} catch (Exception $e) {
    error_log("Error loading data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sina Tavakoli - 3D Product Designer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Background Elements -->
    <div class="gradient-bg"></div>
    <div class="gradient-overlay"></div>
    <div class="grid-pattern"></div>
    
    <!-- Custom Cursor -->
    <div class="cursor"></div>
    <div class="cursor-dot"></div>
    
    <!-- Floating Elements -->
    <div class="floating-element" style="top: 10%; left: 10%; font-size: 60px;">◈</div>
    <div class="floating-element" style="top: 20%; right: 15%; font-size: 40px; animation-delay: 2s;">◉</div>
    <div class="floating-element" style="bottom: 30%; left: 5%; font-size: 50px; animation-delay: 4s;">◆</div>
    
    <!-- Navigation -->
    <nav id="navbar" class="fixed top-0 w-full z-50 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="text-2xl font-bold">
                <span style="background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Sina.</span>
            </div>
            <div class="hidden md:flex items-center gap-8">
                <a href="#about" class="text-gray-300 hover:text-white transition">About</a>
                <a href="#work" class="text-gray-300 hover:text-white transition">Work</a>
                <a href="#tools" class="text-gray-300 hover:text-white transition">Tools</a>
                <a href="#contact" class="text-gray-300 hover:text-white transition">Contact</a>
                <a href="admin/" class="text-gray-300 hover:text-white transition">Admin</a>
            </div>
            <button class="md:hidden text-white" id="mobile-menu">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center px-6 pt-20 relative hero-gradient">
        <div class="max-w-5xl w-full text-center relative z-10">
            <div class="mb-8 fade-in">
                <p class="text-yellow-400 font-medium text-lg"><?php echo htmlspecialchars($heroContent['subtitle'] ?? 'Hi, my name is'); ?></p>
            </div>
            <h1 class="hero-title text-6xl md:text-8xl font-bold mb-6 split-text">
                <?php echo htmlspecialchars($heroContent['name'] ?? 'Sina Tavakoli'); ?>
            </h1>
            <h2 class="hero-subtitle text-4xl md:text-6xl font-light mb-8 text-gray-300 split-text">
                I create <span style="background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">3D experiences</span>.
            </h2>
            <p class="text-xl text-gray-400 mb-12 max-w-3xl mx-auto fade-in">
                <?php echo htmlspecialchars($heroContent['description'] ?? 'Product designer crafting immersive digital experiences that blend creativity with cutting-edge 3D technology'); ?>
            </p>
            <div class="flex justify-center gap-6 fade-in">
                <button class="btn-primary">Book a Call</button>
                
                <!-- Dropdown Menu Button -->
                <div class="dropdown-menu">
                    <button class="btn-secondary" id="viewWorkBtn">
                        View My Work
                        <span class="dropdown-arrow" id="dropdownArrow">▼</span>
                    </button>
                    <div class="dropdown-content" id="dropdownContent">
                        <a href="#work" class="dropdown-item" id="viewOnline">
                            <i class="fas fa-eye"></i>
                            <span>View Online</span>
                        </a>
                        <a href="#" class="dropdown-item" id="printResume">
                            <i class="fas fa-print"></i>
                            <span>Print Resume</span>
                        </a>
                        <a href="#" class="dropdown-item" id="downloadResume">
                            <i class="fas fa-download"></i>
                            <span>Download Resume</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator"></div>
    </section>
    
    <!-- About Section -->
    <section id="about" class="py-24 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="fade-in">
                    <div class="relative">
                        <img src="https://picsum.photos/seed/sina3d/600/700.jpg" 
                             class="rounded-3xl w-full shadow-2xl" 
                             style="border: 2px solid rgba(249, 199, 79, 0.3);" />
                        <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-yellow-400 rounded-2xl flex items-center justify-center text-4xl">
                            3D
                        </div>
                    </div>
                </div>
                
                <div class="space-y-8">
                    <div class="fade-in">
                        <h2 class="text-4xl font-bold mb-6">About Me</h2>
                        <p class="text-lg text-gray-300 leading-relaxed mb-6">
                            Hello! I'm Sina, a passionate product designer with over 3 years of experience creating beautiful, functional digital products with immersive <span class="text-yellow-400">3D elements</span>.
                        </p>
                        <p class="text-lg text-gray-300 leading-relaxed">
                            My passion lies in solving complex problems through design and creating experiences that users love. I specialize in UI/UX design, 3D visualization, and creative direction.
                        </p>
                    </div>
                    
                    <div class="fade-in">
                        <h3 class="text-xl font-semibold mb-4">Skills & Expertise</h3>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($skills as $skill): ?>
                                <span class="skill-pill"><?php echo htmlspecialchars($skill['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="fade-in">
                        <a href="#" class="inline-flex items-center gap-2 text-yellow-400 hover:text-yellow-300 transition">
                            View Resume <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <div class="section-divider"></div>
    
    <!-- Stats Section -->
    <section class="py-16 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="fade-in">
                    <div class="counter" data-target="<?php echo htmlspecialchars($stats['projects'] ?? '50'); ?>">0</div>
                    <p class="text-gray-400 mt-2">Projects Completed</p>
                </div>
                <div class="fade-in">
                    <div class="counter" data-target="<?php echo htmlspecialchars($stats['clients'] ?? '30'); ?>">0</div>
                    <p class="text-gray-400 mt-2">Happy Clients</p>
                </div>
                <div class="fade-in">
                    <div class="counter" data-target="<?php echo htmlspecialchars($stats['experience'] ?? '3'); ?>">0</div>
                    <p class="text-gray-400 mt-2">Years Experience</p>
                </div>
                <div class="fade-in">
                    <div class="counter" data-target="<?php echo htmlspecialchars($stats['awards'] ?? '15'); ?>">0</div>
                    <p class="text-gray-400 mt-2">Awards Won</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Work Section -->
    <section id="work" class="py-24 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 fade-in">Selected Work</h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto fade-in">
                    Explore my latest 3D design projects and creative experiments
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card fade-in">
                        <div class="project-image">
                            <img src="<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-48 object-cover" />
                            <div class="project-overlay"></div>
                        </div>
                        <div class="p-8">
                            <h3 class="text-2xl font-bold mb-3"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="text-gray-400 mb-6"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></p>
                            <div class="flex gap-2">
                                <?php 
                                $tags = json_decode($project['tags'], true) ?: [];
                                foreach (array_slice($tags, 0, 2) as $tag): 
                                ?>
                                    <span class="skill-pill"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <div class="section-divider"></div>
    
    <!-- Tools Section -->
    <section id="tools" class="py-24 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 fade-in">My Toolbox</h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto fade-in">
                    The tools I use to bring 3D experiences to life
                </p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <?php foreach ($tools as $tool): ?>
                    <div class="text-center fade-in">
                        <div class="tool-icon mx-auto mb-4"><?php echo htmlspecialchars($tool['icon']); ?></div>
                        <h3 class="font-semibold mb-2"><?php echo htmlspecialchars($tool['name']); ?></h3>
                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($tool['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section id="contact" class="py-24 px-6">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6 fade-in">Let's Work Together</h2>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto fade-in">
                    Ready to create something amazing? Let's discuss your project
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <div class="contact-card fade-in">
                    <div class="text-5xl mb-4">📧</div>
                    <h3 class="text-2xl font-bold mb-3">Email</h3>
                    <p class="text-gray-400 mb-4">Let's discuss your 3D project</p>
                    <a href="mailto:<?php echo htmlspecialchars($contactContent['email'] ?? 'sina@example.com'); ?>" class="text-yellow-400 hover:text-yellow-300 transition">
                        <?php echo htmlspecialchars($contactContent['email'] ?? 'sina@example.com'); ?>
                    </a>
                </div>
                
                <div class="contact-card fade-in">
                    <div class="text-5xl mb-4">💬</div>
                    <h3 class="text-2xl font-bold mb-3">WhatsApp</h3>
                    <p class="text-gray-400 mb-4">Quick chat about your ideas</p>
                    <a href="#" class="text-yellow-400 hover:text-yellow-300 transition">
                        <?php echo htmlspecialchars($contactContent['phone'] ?? '+98 912 345 6789'); ?>
                    </a>
                </div>
            </div>
            
            <div class="text-center fade-in">
                <div class="flex justify-center gap-4">
                    <a href="#" class="social-link">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-dribbble text-xl"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-behance text-xl"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-gray-800">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-gray-400 fade-in">© <?php echo date('Y'); ?> Sina Tavakoli. All rights reserved.</p>
        </div>
    </footer>
    <script src="js/app.js"></script>
  
</body>
</html>