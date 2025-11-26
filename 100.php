<?php
require_once 'db.php';

// Track visitor
function trackVisitor($page) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d');
    
    $stmt = $pdo->prepare("INSERT INTO visitors (ip_address, visit_date, page_visited) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $date, $page]);
}

trackVisitor('index');

// Get site information
 $stmt = $pdo->query("SELECT * FROM site_info LIMIT 1");
 $siteInfo = $stmt->fetch();

// Get projects
 $stmt = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
 $projects = $stmt->fetchAll();

// Get tools
 $stmt = $pdo->query("SELECT * FROM tools ORDER BY name");
 $tools = $stmt->fetchAll();

// Get visitor statistics
 $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) as total_visitors FROM visitors");
 $visitorCount = $stmt->fetch()['total_visitors'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($siteInfo['title'] ?? 'Sina Tavakoli - 3D Product Designer'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      overflow-x: hidden;
      background: #0a0e27;
      color: #ffffff;
    }
    
    html {
      scroll-behavior: smooth;
    }
    
    /* Custom Cursor */
    .cursor {
      position: fixed;
      width: 40px;
      height: 40px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      pointer-events: none;
      z-index: 9999;
      transition: all 0.1s ease;
      transform: translate(-50%, -50%);
    }
    
    .cursor-dot {
      position: fixed;
      width: 8px;
      height: 8px;
      background: #f9c74f;
      border-radius: 50%;
      pointer-events: none;
      z-index: 9999;
      transform: translate(-50%, -50%);
    }
    
    .cursor.hover {
      width: 60px;
      height: 60px;
      border-color: #f9c74f;
      background: rgba(249, 199, 79, 0.1);
    }
    
    /* Gradient Background */
    .gradient-bg {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(ellipse at top, #1a1f3a 0%, #0a0e27 50%);
      z-index: -2;
    }
    
    /* Animated Gradient Overlay */
    .gradient-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(249, 199, 79, 0.05) 0%, rgba(147, 51, 234, 0.05) 50%, rgba(59, 130, 246, 0.05) 100%);
      z-index: -1;
      animation: gradientShift 20s ease infinite;
    }
    
    @keyframes gradientShift {
      0%, 100% { opacity: 0.05; transform: translateX(0) translateY(0); }
      33% { opacity: 0.08; transform: translateX(-20px) translateY(20px); }
      66% { opacity: 0.08; transform: translateX(20px) translateY(-20px); }
    }
    
    /* Navigation */
    nav {
      backdrop-filter: blur(20px);
      background: rgba(10, 14, 39, 0.8);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
    }
    
    nav.scrolled {
      background: rgba(10, 14, 39, 0.95);
      backdrop-filter: blur(30px);
    }
    
    /* Hero Section */
    .hero-gradient {
      background: radial-gradient(ellipse at center, rgba(249, 199, 79, 0.1) 0%, transparent 70%);
    }
    
    /* Text Animations */
    .text-reveal {
      opacity: 0;
      transform: translateY(50px);
    }
    
    .split-text span {
      display: inline-block;
      opacity: 0;
      transform: translateY(100px);
    }
    
    /* Card Styles */
    .feature-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      transform-style: preserve-3d;
      perspective: 1000px;
    }
    
    .feature-card:hover {
      transform: translateY(-10px) rotateX(5deg);
      background: rgba(255, 255, 255, 0.05);
      border-color: rgba(249, 199, 79, 0.3);
      box-shadow: 0 20px 40px rgba(249, 199, 79, 0.1);
    }
    
    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, transparent 0%, rgba(249, 199, 79, 0.1) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: inherit;
      pointer-events: none;
    }
    
    .feature-card:hover::before {
      opacity: 1;
    }
    
    /* Button Styles */
    .btn-primary {
      background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%);
      color: #0a0e27;
      font-weight: 600;
      padding: 14px 32px;
      border-radius: 100px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .btn-primary::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }
    
    .btn-primary:hover::before {
      width: 300px;
      height: 300px;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(249, 199, 79, 0.3);
    }
    
    .btn-secondary {
      background: transparent;
      color: #ffffff;
      font-weight: 600;
      padding: 14px 32px;
      border-radius: 100px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(249, 199, 79, 0.5);
      transform: translateY(-2px);
    }
    
    /* Dropdown Menu Styles */
    .dropdown-menu {
      position: relative;
      display: inline-block;
    }
    
    .dropdown-content {
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      margin-top: 10px;
      background: rgba(10, 14, 39, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 12px;
      min-width: 200px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      opacity: 0;
      visibility: hidden;
      transform: translateX(-50%) translateY(-10px);
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      z-index: 1000;
    }
    
    .dropdown-content.show {
      opacity: 1;
      visibility: visible;
      transform: translateX(-50%) translateY(0);
    }
    
    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 12px;
      color: #ffffff;
      text-decoration: none;
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
    }
    
    .dropdown-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(249, 199, 79, 0.1), transparent);
      transition: left 0.5s ease;
    }
    
    .dropdown-item:hover::before {
      left: 100%;
    }
    
    .dropdown-item:hover {
      background: rgba(249, 199, 79, 0.1);
      transform: translateX(5px);
    }
    
    .dropdown-item i {
      font-size: 18px;
      color: #f9c74f;
      width: 20px;
      text-align: center;
    }
    
    .dropdown-arrow {
      display: inline-block;
      margin-left: 8px;
      transition: transform 0.3s ease;
    }
    
    .dropdown-arrow.rotate {
      transform: rotate(180deg);
    }
    
    /* Floating Elements */
    .floating-element {
      position: absolute;
      opacity: 0.1;
      animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(10deg); }
    }
    
    /* Grid Pattern */
    .grid-pattern {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
      background-size: 50px 50px;
      z-index: -1;
    }
    
    /* Section Divider */
    .section-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(249, 199, 79, 0.3) 50%, transparent 100%);
      margin: 100px 0;
    }
    
    /* Skill Pills */
    .skill-pill {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 8px 20px;
      border-radius: 100px;
      font-size: 14px;
      transition: all 0.3s ease;
      display: inline-block;
    }
    
    .skill-pill:hover {
      background: rgba(249, 199, 79, 0.1);
      border-color: rgba(249, 199, 79, 0.3);
      transform: translateY(-2px);
    }
    
    /* Project Card */
    .project-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      transform-style: preserve-3d;
    }
    
    .project-card:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }
    
    .project-image {
      position: relative;
      overflow: hidden;
      height: 250px;
    }
    
    .project-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s ease;
    }
    
    .project-card:hover .project-image img {
      transform: scale(1.1);
    }
    
    .project-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(to bottom, transparent 0%, rgba(10, 14, 39, 0.9) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .project-card:hover .project-overlay {
      opacity: 1;
    }
    
    /* Tool Icon */
    .tool-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      transition: all 0.3s ease;
    }
    
    .tool-icon:hover {
      background: rgba(249, 199, 79, 0.1);
      border-color: rgba(249, 199, 79, 0.3);
      transform: translateY(-5px) rotate(5deg);
    }
    
    /* Contact Card */
    .contact-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 40px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .contact-card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(249, 199, 79, 0.1) 0%, transparent 70%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .contact-card:hover::before {
      opacity: 1;
    }
    
    .contact-card:hover {
      transform: translateY(-5px);
      border-color: rgba(249, 199, 79, 0.3);
    }
    
    /* Loading Animation */
    .fade-in {
      opacity: 0;
      transform: translateY(30px);
    }
    
    /* Social Links */
    .social-link {
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }
    
    .social-link:hover {
      background: rgba(249, 199, 79, 0.1);
      border-color: rgba(249, 199, 79, 0.3);
      transform: translateY(-3px);
    }
    
    /* Scroll Indicator */
    .scroll-indicator {
      position: fixed;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      width: 30px;
      height: 50px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 20px;
      display: flex;
      justify-content: center;
      padding-top: 10px;
      z-index: 10;
    }
    
    .scroll-indicator::before {
      content: '';
      width: 4px;
      height: 10px;
      background: #f9c74f;
      border-radius: 2px;
      animation: scroll 2s infinite;
    }
    
    @keyframes scroll {
      0% { transform: translateY(0); opacity: 1; }
      100% { transform: translateY(20px); opacity: 0; }
    }
    
    /* Number Counter */
    .counter {
      font-size: 48px;
      font-weight: 700;
      background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .hero-title {
        font-size: 48px !important;
      }
      
      .hero-subtitle {
        font-size: 32px !important;
      }
      
      .dropdown-content {
        left: 0;
        transform: translateX(0);
        margin-top: 8px;
      }
      
      .dropdown-content.show {
        transform: translateX(0) translateY(0);
      }
    }
  </style>
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
        <a href="admin/login.php" class="btn-primary">Admin</a>
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
        <p class="text-yellow-400 font-medium text-lg">Hi, my name is</p>
      </div>
      <h1 class="hero-title text-6xl md:text-8xl font-bold mb-6 split-text">
        Sina Tavakoli
      </h1>
      <h2 class="hero-subtitle text-4xl md:text-6xl font-light mb-8 text-gray-300 split-text">
        I create <span style="background: linear-gradient(135deg, #f9c74f 0%, #ffd166 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">3D experiences</span>
      </h2>
      <p class="text-xl text-gray-400 mb-12 max-w-3xl mx-auto fade-in">
        <?php echo htmlspecialchars($siteInfo['subtitle'] ?? 'Product designer crafting immersive digital experiences that blend creativity with cutting-edge 3D technology'); ?>
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
              <?php echo htmlspecialchars($siteInfo['about_text'] ?? 'Hello! I\'m Sina, a passionate product designer with over 3 years of experience creating beautiful, functional digital products with immersive <span class="text-yellow-400">3D elements</span>.'); ?>
            </p>
            <p class="text-lg text-gray-300 leading-relaxed">
              My passion lies in solving complex problems through design and creating experiences that users love. I specialize in UI/UX design, 3D visualization, and creative direction.
            </p>
          </div>
          
          <div class="fade-in">
            <h3 class="text-xl font-semibold mb-4">Skills & Expertise</h3>
            <div class="flex flex-wrap gap-3">
              <span class="skill-pill">Product Design</span>
              <span class="skill-pill">UI/UX Design</span>
              <span class="skill-pill">3D Design</span>
              <span class="skill-pill">Prototyping</span>
              <span class="skill-pill">Creative Direction</span>
              <span class="skill-pill">Motion Design</span>
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
          <div class="counter" data-target="<?php echo count($projects); ?>">0</div>
          <p class="text-gray-400 mt-2">Projects Completed</p>
        </div>
        <div class="fade-in">
          <div class="counter" data-target="30">0</div>
          <p class="text-gray-400 mt-2">Happy Clients</p>
        </div>
        <div class="fade-in">
          <div class="counter" data-target="3">0</div>
          <p class="text-gray-400 mt-2">Years Experience</p>
        </div>
        <div class="fade-in">
          <div class="counter" data-target="<?php echo $visitorCount; ?>">0</div>
          <p class="text-gray-400 mt-2">Visitors</p>
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
            <img src="<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" />
            <div class="project-overlay"></div>
          </div>
          <div class="p-8">
            <h3 class="text-2xl font-bold mb-3"><?php echo htmlspecialchars($project['title']); ?></h3>
            <p class="text-gray-400 mb-6"><?php echo htmlspecialchars($project['description']); ?></p>
            <div class="flex gap-2">
              <?php 
              $tags = explode(',', $project['tags']);
              foreach ($tags as $tag): 
              ?>
              <span class="skill-pill"><?php echo htmlspecialchars(trim($tag)); ?></span>
              <?php endforeach; ?>
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
          <a href="mailto:<?php echo htmlspecialchars($siteInfo['email'] ?? 'sina@example.com'); ?>" class="text-yellow-400 hover:text-yellow-300 transition">
            <?php echo htmlspecialchars($siteInfo['email'] ?? 'sina@example.com'); ?>
          </a>
        </div>
        
        <div class="contact-card fade-in">
          <div class="text-5xl mb-4">💬</div>
          <h3 class="text-2xl font-bold mb-3">WhatsApp</h3>
          <p class="text-gray-400 mb-4">Quick chat about your ideas</p>
          <a href="#" class="text-yellow-400 hover:text-yellow-300 transition">
            <?php echo htmlspecialchars($siteInfo['phone'] ?? '+98 912 345 6789'); ?>
          </a>
        </div>
      </div>
      
      <div class="text-center fade-in">
        <div class="flex justify-center gap-4">
          <a href="<?php echo htmlspecialchars($siteInfo['linkedin'] ?? '#'); ?>" class="social-link">
            <i class="fab fa-linkedin text-xl"></i>
          </a>
          <a href="<?php echo htmlspecialchars($siteInfo['dribbble'] ?? '#'); ?>" class="social-link">
            <i class="fab fa-dribbble text-xl"></i>
          </a>
          <a href="<?php echo htmlspecialchars($siteInfo['behance'] ?? '#'); ?>" class="social-link">
            <i class="fab fa-behance text-xl"></i>
          </a>
          <a href="<?php echo htmlspecialchars($siteInfo['github'] ?? '#'); ?>" class="social-link">
            <i class="fab fa-github text-xl"></i>
          </a>
        </div>
      </div>
    </div>
  </section>
  
  <!-- Footer -->
  <footer class="py-12 px-6 border-t border-gray-800">
    <div class="max-w-7xl mx-auto text-center">
      <p class="text-gray-400">© <?php echo date('Y'); ?> Sina Tavakoli. All rights reserved.</p>
    </div>
  </footer>
  
  <script>
    // Initialize GSAP
    gsap.registerPlugin(ScrollTrigger, TextPlugin);
    
    // Custom Cursor
    const cursor = document.querySelector('.cursor');
    const cursorDot = document.querySelector('.cursor-dot');
    
    document.addEventListener('mousemove', (e) => {
      cursor.style.left = e.clientX + 'px';
      cursor.style.top = e.clientY + 'px';
      cursorDot.style.left = e.clientX + 'px';
      cursorDot.style.top = e.clientY + 'px';
    });
    
    // Add hover effect to interactive elements
    const interactiveElements = document.querySelectorAll('a, button, .project-card, .tool-icon, .contact-card, .social-link, .dropdown-item');
    interactiveElements.forEach(el => {
      el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
      el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
    });
    
    // Dropdown Menu Functionality
    const viewWorkBtn = document.getElementById('viewWorkBtn');
    const dropdownContent = document.getElementById('dropdownContent');
    const dropdownArrow = document.getElementById('dropdownArrow');
    let isDropdownOpen = false;
    
    viewWorkBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      isDropdownOpen = !isDropdownOpen;
      
      if (isDropdownOpen) {
        dropdownContent.classList.add('show');
        dropdownArrow.classList.add('rotate');
        
        // Animate dropdown items
        const dropdownItems = dropdownContent.querySelectorAll('.dropdown-item');
        gsap.fromTo(dropdownItems, 
          { opacity: 0, y: -10, x: 20 },
          { 
            opacity: 1, 
            y: 0, 
            x: 0,
            duration: 0.3,
            stagger: 0.1,
            ease: "power2.out"
          }
        );
      } else {
        dropdownContent.classList.remove('show');
        dropdownArrow.classList.remove('rotate');
      }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.dropdown-menu') && isDropdownOpen) {
        isDropdownOpen = false;
        dropdownContent.classList.remove('show');
        dropdownArrow.classList.remove('rotate');
      }
    });
    
    // Handle dropdown item clicks
    document.getElementById('viewOnline').addEventListener('click', (e) => {
      e.preventDefault();
      // Smooth scroll to work section
      document.getElementById('work').scrollIntoView({ behavior: 'smooth' });
      isDropdownOpen = false;
      dropdownContent.classList.remove('show');
      dropdownArrow.classList.remove('rotate');
    });
    
    document.getElementById('printResume').addEventListener('click', (e) => {
      e.preventDefault();
      // Create print-friendly version
      window.print();
      isDropdownOpen = false;
      dropdownContent.classList.remove('show');
      dropdownArrow.classList.remove('rotate');
    });
    
    document.getElementById('downloadResume').addEventListener('click', (e) => {
      e.preventDefault();
      // Create a sample resume file download
      const resumeContent = `
Sina Tavakoli - 3D Product Designer
=====================================

Contact:
Email: <?php echo htmlspecialchars($siteInfo['email'] ?? 'sina@example.com'); ?>
Phone: <?php echo htmlspecialchars($siteInfo['phone'] ?? '+98 912 345 6789'); ?>

Experience:
- 3+ years in Product Design
- Specialized in 3D UI/UX Design
- Expert in modern design tools

Skills:
- Product Design
- UI/UX Design
- 3D Design & Visualization
- Prototyping
- Motion Design
- Creative Direction

Tools:
<?php foreach ($tools as $tool): ?>
- <?php echo htmlspecialchars($tool['name']); ?>: <?php echo htmlspecialchars($tool['description']); ?>
<?php endforeach; ?>

Portfolio:
Available at: https://sinatavakoli.com
      `;
      
      const blob = new Blob([resumeContent], { type: 'text/plain' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'Sina_Tavakoli_Resume.txt';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      
      isDropdownOpen = false;
      dropdownContent.classList.remove('show');
      dropdownArrow.classList.remove('rotate');
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
      const navbar = document.getElementById('navbar');
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
    
    // Split text animation
    function splitText(element) {
      const text = element.innerText;
      element.innerHTML = '';
      text.split('').forEach(char => {
        const span = document.createElement('span');
        span.innerText = char === ' ' ? '\u00A0' : char;
        element.appendChild(span);
      });
      return element.querySelectorAll('span');
    }
    
    // Animate hero title
    const heroTitle = document.querySelector('.hero-title');
    const heroTitleChars = splitText(heroTitle);
    
    gsap.to(heroTitleChars, {
      opacity: 1,
      y: 0,
      duration: 0.8,
      stagger: 0.05,
      ease: "power3.out",
      delay: 0.5
    });
    
    // Animate hero subtitle
    const heroSubtitle = document.querySelector('.hero-subtitle');
    const heroSubtitleChars = splitText(heroSubtitle);
    
    gsap.to(heroSubtitleChars, {
      opacity: 1,
      y: 0,
      duration: 0.8,
      stagger: 0.03,
      ease: "power3.out",
      delay: 1
    });
    
    // Fade in animations
    gsap.utils.toArray('.fade-in').forEach(element => {
      gsap.to(element, {
        opacity: 1,
        y: 0,
        duration: 1,
        ease: "power3.out",
        scrollTrigger: {
          trigger: element,
          start: "top 85%",
          end: "bottom 15%",
          toggleActions: "play none none reverse"
        }
      });
    });
    
    // Counter animation
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
      const target = parseInt(counter.getAttribute('data-target'));
      
      ScrollTrigger.create({
        trigger: counter,
        start: "top 80%",
        onEnter: () => {
          gsap.to(counter, {
            innerText: target,
            duration: 2,
            ease: "power2.out",
            snap: { innerText: 1 },
            onUpdate: function() {
              counter.innerText = Math.ceil(counter.innerText);
            }
          });
        }
      });
    });
    
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        if (!this.closest('.dropdown-menu')) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        }
      });
    });
    
    // Project cards hover effect
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
      card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = (y - centerY) / 20;
        const rotateY = (centerX - x) / 20;
        
        gsap.to(card, {
          rotationX: rotateX,
          rotationY: rotateY,
          transformPerspective: 1000,
          duration: 0.3
        });
      });
      
      card.addEventListener('mouseleave', () => {
        gsap.to(card, {
          rotationX: 0,
          rotationY: 0,
          duration: 0.3
        });
      });
    });
    
    // Hide scroll indicator on scroll
    window.addEventListener('scroll', () => {
      const scrollIndicator = document.querySelector('.scroll-indicator');
      if (window.scrollY > 100) {
        gsap.to(scrollIndicator, { opacity: 0, duration: 0.3 });
      } else {
        gsap.to(scrollIndicator, { opacity: 1, duration: 0.3 });
      }
    });
    
    // Mobile menu toggle
    const mobileMenu = document.getElementById('mobile-menu');
    let isMenuOpen = false;
    
    mobileMenu.addEventListener('click', () => {
      isMenuOpen = !isMenuOpen;
      // Add mobile menu functionality here if needed
    });
    
    // Parallax effect for floating elements
    gsap.utils.toArray('.floating-element').forEach(element => {
      gsap.to(element, {
        y: -100,
        ease: "none",
        scrollTrigger: {
          trigger: element,
          start: "top bottom",
          end: "bottom top",
          scrub: true
        }
      });
    });
    
    // Button ripple effect
    document.querySelectorAll('.btn-primary').forEach(button => {
      button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
          ripple.remove();
        }, 600);
      });
    });
  </script>
</body>
</html>