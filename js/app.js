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
Email: sina@example.com
Phone: +98 912 345 6789

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
- Figma
- Spline
- Blender
- After Effects
- Three.js
- Principle
- Cinema 4D
- React Three Fiber

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