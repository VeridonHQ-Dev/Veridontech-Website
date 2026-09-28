/**
 * Veridon Technologies — Mobile Navigation Menu Script
 * Handles toggle, backdrop, keyboard navigation, focus trap, and responsive behavior.
 */
(function () {
  'use strict';

  function initMobileNav() {
    var toggleBtn = document.getElementById('mobileToggle');
    var navDrawer = document.getElementById('mobileNav');
    var backdrop  = document.getElementById('mobileNavBackdrop');
    var closeBtn  = document.getElementById('mobileNavClose');

    // If toggle button doesn't exist, we do not proceed
    if (!toggleBtn || !navDrawer || !backdrop) return;

    var isOpen = false;

    function openMenu() {
      if (isOpen) return;
      isOpen = true;
      toggleBtn.classList.add('is-active');
      toggleBtn.setAttribute('aria-expanded', 'true');
      toggleBtn.setAttribute('aria-label', 'Close menu');

      backdrop.classList.add('is-open');
      backdrop.setAttribute('aria-hidden', 'false');

      navDrawer.classList.add('is-open');
      navDrawer.setAttribute('aria-hidden', 'false');

      document.body.classList.add('mobile-nav-open');

      // Focus first link or close button for accessibility
      if (closeBtn) {
        setTimeout(function () {
          closeBtn.focus();
        }, 100);
      }
    }

    function closeMenu() {
      if (!isOpen) return;
      isOpen = false;
      toggleBtn.classList.remove('is-active');
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.setAttribute('aria-label', 'Open menu');

      backdrop.classList.remove('is-open');
      backdrop.setAttribute('aria-hidden', 'true');

      navDrawer.classList.remove('is-open');
      navDrawer.setAttribute('aria-hidden', 'true');

      document.body.classList.remove('mobile-nav-open');

      // Return focus to toggle button
      toggleBtn.focus();
    }

    // Toggle button click
    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    // Close button click
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        closeMenu();
      });
    }

    // Backdrop click
    backdrop.addEventListener('click', function () {
      closeMenu();
    });

    // Keyboard support: Escape key closes menu
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isOpen) {
        closeMenu();
      }
    });

    // Close when clicking any navigation link
    var navLinks = navDrawer.querySelectorAll('.mobile-nav-link, .mobile-nav-cta');
    navLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        closeMenu();
      });
    });

    // Auto-close on resize to desktop viewport (>980px)
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (window.innerWidth > 980 && isOpen) {
          closeMenu();
        }
      }, 100);
    });

    // Sync aria-current for the active page if not already set
    var currentPath = window.location.pathname.split('/').pop() || 'index.html';
    navDrawer.querySelectorAll('.mobile-nav-link').forEach(function (link) {
      var href = link.getAttribute('href');
      if (href === currentPath || (currentPath === '' && href === 'index.html')) {
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  // Initialize once DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileNav);
  } else {
    initMobileNav();
  }
})();
