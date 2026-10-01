<?php
require_once 'config/db.php';
require_once 'config/session.php';
if ($currentUser) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confederates Student Council &bull; Resource Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .modal-body .form-label { font-size: 1.05rem; margin-bottom: 0.5rem; }
      @media print {
          @page { size: landscape; margin: 10mm; }
          body { overflow: visible !important; }
          .app-main { overflow: visible !important; height: auto !important; }
          .table-responsive { overflow: visible !important; }
          .no-print { display: none !important; }
      }
      /* Sidebar Mobile Toggle CSS */
      .app-sidebar { transition: transform 0.3s ease; z-index: 1040; }
      @media (max-width: 991.98px) {
          .app-sidebar {
              position: fixed; top: 0; left: 0; height: 100vh; width: 270px;
              transform: translateX(-100%); background: #fff;
          }
          .app-sidebar.show-sidebar { transform: translateX(0); }
      }
      
      /* Mobile Login Responsive Layout */
      @media (max-width: 767.98px) {
          .login-left-panel {
              height: auto !important;
              padding: 1.25rem 1rem !important;
          }
          .login-logo {
              width: 40px !important;
              height: 40px !important;
          }
          .login-logo span {
              font-size: 7px !important;
              line-height: 1 !important;
          }
          .login-title-1 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; margin-right: 0.3rem !important; }
          .login-title-2 { font-size: 1.2rem !important; display: inline-block; margin: 0 !important; }
          .login-subtitle { font-size: 0.7rem !important; letter-spacing: 0.5px !important; margin-top: 0.3rem !important; }
          .login-logo-gap { gap: 0.75rem !important; margin-bottom: 0.75rem !important; }
          .login-text-container { padding: 0 !important; max-width: 100% !important; margin-left: 0 !important; }
          .login-right-panel { height: calc(100vh - 120px) !important; justify-content: flex-start !important; padding-top: 2rem !important; }
      }
  </style>
</head>
<body>


  <!-- ==========================================================
       SPLIT-SCREEN LOGIN WITH CANVAS ANIMATION
       ========================================================== -->
  <div class="row g-0 vh-100">
    <!-- Left Panel: Brand & Animation -->
    <div class="col-md-6 position-relative d-flex align-items-center justify-content-center overflow-hidden login-left-panel" style="background-color: #1d4ed8;">
      <!-- === START OF ANTIGRAVITY ANIMATION HTML (REMOVE IF NOT WANTED) === -->
      <canvas id="particlesCanvas" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 0; pointer-events: none;"></canvas>
      <!-- === END OF ANTIGRAVITY ANIMATION HTML === -->
      
      <div class="text-white text-start p-5 position-relative w-100 ms-md-4 login-text-container" style="z-index: 1; max-width: 650px;">
        <!-- Logos (Replace src with actual image paths) -->
        <div class="d-flex align-items-center gap-4 mb-4 login-logo-gap">
           <!-- SU CCS LOGO IMAGE -->
           <div class="bg-white rounded-circle shadow d-flex align-items-center justify-content-center login-logo" style="width: 80px; height: 80px; overflow: hidden;">
              <span class="text-primary fw-bold text-center" style="font-size: 11px;">SU CCS<br>LOGO</span>
              <!-- <img src="path/to/su-ccs-logo.png" alt="SU CCS" style="width: 100%; height: auto;"> -->
           </div>
           <!-- CCS CONFEDERATE STUDENT COUNCIL LOGO IMAGE -->
           <div class="bg-white rounded-circle shadow d-flex align-items-center justify-content-center login-logo" style="width: 80px; height: 80px; overflow: hidden;">
              <span class="text-primary fw-bold text-center" style="font-size: 11px;">CONFED<br>LOGO</span>
              <!-- <img src="path/to/confed-logo.png" alt="Confederates" style="width: 100%; height: auto;"> -->
           </div>
        </div>
        
        <!-- Text content -->
        <div class="d-block">
          <h1 class="fw-bold display-4 mb-0 login-title-1" style="line-height: 1.1; letter-spacing: -1px;">CONFEDERATES</h1>
          <h1 class="fw-bold display-4 mb-4 login-title-2" style="line-height: 1.1; letter-spacing: -1px;">STUDENT COUNCIL</h1>
          <h4 class="fw-light text-uppercase login-subtitle" style="letter-spacing: 1.5px; font-size: 1.3rem;">ITEM MANAGEMENT AND LENDING SYSTEM</h4>
        </div>
      </div>
    </div>
    
    <!-- Right Panel: Login Form -->
    <div class="col-md-6 d-flex flex-column align-items-center justify-content-center bg-light p-4 login-right-panel">
      <div style="max-width: 400px; width: 100%;">
        <?php if (!empty($alert['message'])): ?>
          <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show rounded-4 py-3 px-4 shadow-sm mb-4">
            <span class="fw-semibold"><?= htmlspecialchars($alert['message']) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 p-4 p-md-5 rounded-4">
          <h4 class="fw-bold mb-4 text-center">Sign In</h4>
          <form method="POST" action="actions/auth_action.php">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" id="loginEmail" name="email" class="form-control" placeholder="user@csc.edu.ph" required autofocus>
            </div>
            <div class="mb-4">
              <label class="form-label">Password</label>
              <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <!-- Quick Sign-In Buttons (Testing Purposes) -->
            <div class="bg-light p-3 rounded border text-center mb-4 small">
              <span class="text-muted d-block mb-2">Quick Sign-In (Testing):</span>
              <div class="btn-group w-100">
                <!-- Council role is effectively the admin/core -->
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('loginEmail').value='admin@csc.edu.ph'; document.getElementById('loginPassword').value='admin123';">
                  Council
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('loginEmail').value='staff@csc.edu.ph'; document.getElementById('loginPassword').value='staff123';">
                  Committee
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- === START OF ANTIGRAVITY ANIMATION SCRIPT (REMOVE IF NOT WANTED) === -->
  <script>
    // Antigravity-style Particle Animation for Left Panel
    const canvas = document.getElementById('particlesCanvas');
    if(canvas) {
        const ctx = canvas.getContext('2d');
        let width, height;
        let particles = [];
        const mouse = { x: -1000, y: -1000 };
        
        function resize() {
            width = canvas.width = canvas.parentElement.clientWidth;
            height = canvas.height = canvas.parentElement.clientHeight;
        }
        window.addEventListener('resize', resize);
        
        // Track mouse movement over the left panel
        canvas.parentElement.addEventListener('mousemove', e => {
            const rect = canvas.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        });
        canvas.parentElement.addEventListener('mouseleave', () => {
            mouse.x = -1000;
            mouse.y = -1000;
        });
        
        // Simple Particle class
        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 1;
                this.vy = (Math.random() - 0.5) * 1;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if(this.x < 0 || this.x > width) this.vx *= -1;
                if(this.y < 0 || this.y > height) this.vy *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, 2, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(220, 220, 220, 0.4)';
                ctx.fill();
            }
        }
        
        function init() {
            resize();
            particles = [];
            for(let i=0; i<60; i++) particles.push(new Particle());
            animate();
        }
        
        function animate() {
            ctx.clearRect(0, 0, width, height);
            for(let i=0; i<particles.length; i++) {
                particles[i].update();
                particles[i].draw();
                
                // Draw connecting lines between particles
                for(let j=i+1; j<particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    if(dist < 100) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = `rgba(220, 220, 220, ${0.3 - (dist/100)*0.3})`;
                        ctx.stroke();
                    }
                }
                
                // Draw line from particle to cursor
                const dx = particles[i].x - mouse.x;
                const dy = particles[i].y - mouse.y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if(dist < 150) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = `rgba(220, 220, 220, ${0.5 - (dist/150)*0.5})`;
                    ctx.stroke();
                }
            }
            requestAnimationFrame(animate);
        }
        init();
    }
  </script>
  <!-- === END OF ANTIGRAVITY ANIMATION SCRIPT === -->

</body>
</html>
