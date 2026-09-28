document.addEventListener("DOMContentLoaded", () => {
  /* ================================
     1. Slider Logic
  ================================ */
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".slider-dot");
  let currentSlide = 0;
  let slideInterval;

  function goToSlide(index) {
    slides[currentSlide].classList.remove("active");
    dots[currentSlide].classList.remove("active");
    
    currentSlide = index;
    
    slides[currentSlide].classList.add("active");
    dots[currentSlide].classList.add("active");
  }

  function nextSlide() {
    let nextIndex = (currentSlide + 1) % slides.length;
    goToSlide(nextIndex);
  }

  function startSlider() {
    slideInterval = setInterval(nextSlide, 5000); // 5 seconds per slide
  }

  function stopSlider() {
    clearInterval(slideInterval);
  }

  dots.forEach(dot => {
    dot.addEventListener("click", function() {
      stopSlider();
      goToSlide(parseInt(this.getAttribute("data-index")));
      startSlider();
    });
  });

  if(slides.length > 0) {
    startSlider();
  }

  /* ================================
     2. Three.js Network Animation
  ================================ */
  const canvas = document.getElementById("hero-canvas");
  if (!canvas || typeof THREE === "undefined") return;

  const scene = new THREE.Scene();
  // We want the background to be transparent so the HTML background images show through
  // If we want it purely over the image, we don't set background color.

  const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
  camera.position.z = 150;

  const renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
  
  // Size the renderer
  const heroSection = document.getElementById("hero");
  function resize() {
    if(!heroSection) return;
    const width = heroSection.clientWidth;
    const height = heroSection.clientHeight;
    renderer.setSize(width, height);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
  }
  window.addEventListener("resize", resize);
  resize();

  // Create Particles
  const particleCount = 200;
  const geometry = new THREE.BufferGeometry();
  const positions = new Float32Array(particleCount * 3);
  const velocities = [];

  for (let i = 0; i < particleCount; i++) {
    positions[i * 3] = (Math.random() - 0.5) * 400; // x
    positions[i * 3 + 1] = (Math.random() - 0.5) * 400; // y
    positions[i * 3 + 2] = (Math.random() - 0.5) * 200; // z

    velocities.push({
      x: (Math.random() - 0.5) * 0.5,
      y: (Math.random() - 0.5) * 0.5,
      z: (Math.random() - 0.5) * 0.5
    });
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

  const material = new THREE.PointsMaterial({
    color: 0xC89B3C, // using the gold variable from CSS
    size: 1.5,
    transparent: true,
    opacity: 0.8
  });

  const particles = new THREE.Points(geometry, material);
  scene.add(particles);

  // Create Lines
  const lineMaterial = new THREE.LineBasicMaterial({
    color: 0xC89B3C,
    transparent: true,
    opacity: 0.15
  });

  // We will dynamically build the lines based on distance in the render loop,
  // but to be performant we can just do a simpler mesh or just rotate the static network.
  // Let's create a static set of lines that rotate with the particles.
  
  const lineGeometry = new THREE.BufferGeometry();
  const linePositions = [];

  for (let i = 0; i < particleCount; i++) {
    for (let j = i + 1; j < particleCount; j++) {
      let dx = positions[i * 3] - positions[j * 3];
      let dy = positions[i * 3 + 1] - positions[j * 3 + 1];
      let dz = positions[i * 3 + 2] - positions[j * 3 + 2];
      let dist = Math.sqrt(dx*dx + dy*dy + dz*dz);
      
      if (dist < 45) { // connect if close
        linePositions.push(
          positions[i * 3], positions[i * 3 + 1], positions[i * 3 + 2],
          positions[j * 3], positions[j * 3 + 1], positions[j * 3 + 2]
        );
      }
    }
  }

  lineGeometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));
  const lines = new THREE.LineSegments(lineGeometry, lineMaterial);
  scene.add(lines);

  // Mouse interaction
  let mouseX = 0;
  let mouseY = 0;
  let targetX = 0;
  let targetY = 0;

  document.addEventListener('mousemove', (event) => {
    mouseX = (event.clientX - window.innerWidth / 2);
    mouseY = (event.clientY - window.innerHeight / 2);
  });

  // Animation Loop
  function animate() {
    requestAnimationFrame(animate);

    targetX = mouseX * 0.001;
    targetY = mouseY * 0.001;

    particles.rotation.y += 0.001;
    particles.rotation.x += 0.0005;
    lines.rotation.y += 0.001;
    lines.rotation.x += 0.0005;

    // slight parallax based on mouse
    scene.rotation.y += 0.05 * (targetX - scene.rotation.y);
    scene.rotation.x += 0.05 * (targetY - scene.rotation.x);

    renderer.render(scene, camera);
  }

  animate();
});
