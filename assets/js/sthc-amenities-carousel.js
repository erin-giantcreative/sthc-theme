/**
 * STHC Amenities Carousel
 *
 * Behaviour rules:
 * - Only one slide visible at a time.
 * - Autoplay moves to the next slide after a per-slide duration.
 * - Autoplay pauses:
 *   - when the user hovers or focuses inside the carousel;
 *   - when the user presses the "Pause slide rotation" button;
 *   - when the browser / OS has prefers-reduced-motion enabled.
 * - When a user clicks a nav item:
 *   - The matching slide becomes active.
 *   - The autoplay timer resets for that slide.
 * - Progress bar:
 *   - Each nav item has a small line at the bottom.
 *   - JS animates a scaleX() from 0 → 1 for the active slide.
 */

(function () {
  'use strict';

  /**
   * Helper: turn NodeList into a plain array.
   */
  function toArray(nodeList) {
    return Array.prototype.slice.call(nodeList || []);
  }

  /**
   * Initialise one carousel instance.
   *
   * @param {HTMLElement} root The root <section> element.
   */
  function initCarousel(root) {
    if (!root) return;

    var tabs = toArray(root.querySelectorAll('[data-carousel-tab]'));
    var slides = toArray(root.querySelectorAll('[data-carousel-slide]'));
    var pauseButton = root.querySelector('[data-carousel-pause]');

    if (tabs.length === 0 || slides.length === 0) {
      // Nothing to control.
      return;
    }

    // Index of the currently active slide.
    var currentIndex = 0;

    // Animation and timing handles.
    var rafId = null;
    var autoplayStart = null;
    var autoplayDuration = 0;

    // State flags.
    var isPausedByHover = false;
    var isPausedByButton = false;

    // Respect user system setting for reduced motion.
    var reduceMotionQuery = window.matchMedia
      ? window.matchMedia('(prefers-reduced-motion: reduce)')
      : null;

    var prefersReducedMotion =
      reduceMotionQuery && typeof reduceMotionQuery.matches === 'boolean'
        ? reduceMotionQuery.matches
        : false;

    /**
     * Read autoplay duration from the current slide.
     * Falls back to 8000ms if not set or invalid.
     */
    function getDurationForIndex(index) {
      var tab = tabs[index];
      if (!tab) return 8000;

      var raw = parseInt(tab.getAttribute('data-autoplay-ms'), 10);
      if (!raw || raw < 0) {
        return 8000;
      }
      return raw;
    }

    /**
     * Update which slide is active.
     * Handles:
     * - aria-selected
     * - tabindex
     * - "hidden" attribute
     * - is-active CSS class
     */
    function setActiveSlide(index, userInitiated) {
      if (index === currentIndex && !userInitiated) {
        return;
      }

      if (index < 0 || index >= slides.length) {
        index = 0;
      }

      // Deactivate current tab + slide.
      tabs.forEach(function (tab, i) {
        var isActive = i === index;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      slides.forEach(function (slide, i) {
        var isActive = i === index;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });

      currentIndex = index;

      // Reset and restart autoplay after a user action,
      // unless autoplay is paused or reduced motion is active.
      resetProgressBar();
      restartAutoplay();
    }

    /**
     * Move to the next slide in the set.
     */
    function goToNextSlide() {
      var nextIndex = currentIndex + 1;
      if (nextIndex >= slides.length) {
        nextIndex = 0;
      }
      setActiveSlide(nextIndex, false);
    }

    /**
     * Reset progress bar visual state.
     * Sets transform scaleX to 0 for all bars.
     */
    function resetProgressBar() {
      tabs.forEach(function (tab) {
        var bar = tab.querySelector('[data-carousel-progress-bar]');
        if (bar) {
          bar.style.transform = 'scaleX(0)';
        }
      });
    }

    /**
     * Update progress bar for current slide.
     *
     * @param {number} ratio Value between 0 and 1.
     */
    function updateProgressBar(ratio) {
      var tab = tabs[currentIndex];
      if (!tab) return;

      var bar = tab.querySelector('[data-carousel-progress-bar]');
      if (!bar) return;

      var clamped = Math.max(0, Math.min(1, ratio));
      bar.style.transform = 'scaleX(' + clamped + ')';
    }

    /**
     * Stop the autoplay loop and cancel animation frame.
     */
    function stopAutoplay() {
      if (rafId !== null) {
        window.cancelAnimationFrame(rafId);
        rafId = null;
      }
      autoplayStart = null;
      autoplayDuration = 0;
    }

    /**
     * Start or restart autoplay for the current slide.
     * Does nothing if:
     * - user requested pause via button, or
     * - browser prefers reduced motion, or
     * - carousel is hovered/focused.
     */
    function restartAutoplay() {
      stopAutoplay();
      resetProgressBar();

      if (isPausedByButton || isPausedByHover || prefersReducedMotion) {
        return;
      }

      autoplayDuration = getDurationForIndex(currentIndex);
      autoplayStart = performance.now();

      function tick(now) {
        // Time elapsed since this slide became active.
        var elapsed = now - autoplayStart;

        // Progress ratio between 0 and 1.
        var ratio = autoplayDuration > 0 ? elapsed / autoplayDuration : 1;
        updateProgressBar(ratio);

        if (elapsed >= autoplayDuration) {
          // Slide complete. Move to next and restart.
          goToNextSlide();
          return;
        }

        rafId = window.requestAnimationFrame(tick);
      }

      rafId = window.requestAnimationFrame(tick);
    }

    /**
     * Handle click on a nav tab.
     */
    function onTabClick(event) {
      var tab = event.currentTarget;
      var index = parseInt(tab.getAttribute('data-carousel-tab'), 10);

      if (isNaN(index)) {
        return;
      }

      setActiveSlide(index, true);
      tab.focus();
    }

    /**
     * Handle keyboard control on tabs:
     * - ArrowLeft / ArrowRight move between tabs.
     * - Home / End move to first/last.
     */
    function onTabKeydown(event) {
      var key = event.key;

      var handled = false;
      var newIndex = currentIndex;

      if (key === 'ArrowRight') {
        newIndex = currentIndex + 1;
        if (newIndex >= tabs.length) newIndex = 0;
        handled = true;
      } else if (key === 'ArrowLeft') {
        newIndex = currentIndex - 1;
        if (newIndex < 0) newIndex = tabs.length - 1;
        handled = true;
      } else if (key === 'Home') {
        newIndex = 0;
        handled = true;
      } else if (key === 'End') {
        newIndex = tabs.length - 1;
        handled = true;
      }

      if (handled) {
        event.preventDefault();
        setActiveSlide(newIndex, true);
        tabs[newIndex].focus();
      }
    }

    /**
     * Toggle pause state from the pause button.
     */
    function onPauseButtonClick() {
      isPausedByButton = !isPausedByButton;
      pauseButton.setAttribute('aria-pressed', isPausedByButton ? 'true' : 'false');

      if (isPausedByButton) {
        stopAutoplay();
        resetProgressBar();
      } else {
        restartAutoplay();
      }
    }

    /**
     * Hover / focus tracking:
     * - When mouse enters or focus moves into the carousel,
     *   we pause autoplay.
     * - When mouse leaves and focus leaves, autoplay can resume.
     */
    function onMouseEnter() {
      isPausedByHover = true;
      stopAutoplay();
    }

    function onMouseLeave() {
      isPausedByHover = false;
      restartAutoplay();
    }

    function onFocusIn() {
      isPausedByHover = true;
      stopAutoplay();
    }

    function onFocusOut(event) {
      // Only resume when focus leaves the carousel entirely.
      if (!root.contains(event.relatedTarget)) {
        isPausedByHover = false;
        restartAutoplay();
      }
    }

    // ------------------------------------------------------------------
    // Event wiring
    // ------------------------------------------------------------------

    tabs.forEach(function (tab) {
      tab.addEventListener('click', onTabClick);
      tab.addEventListener('keydown', onTabKeydown);
    });

    if (pauseButton) {
      pauseButton.addEventListener('click', onPauseButtonClick);
    }

    root.addEventListener('mouseenter', onMouseEnter);
    root.addEventListener('mouseleave', onMouseLeave);
    root.addEventListener('focusin', onFocusIn);
    root.addEventListener('focusout', onFocusOut);

    if (reduceMotionQuery && typeof reduceMotionQuery.addEventListener === 'function') {
      // If the user changes their system motion preference while the
      // page is open, react to that.
      reduceMotionQuery.addEventListener('change', function (event) {
        prefersReducedMotion = event.matches;
        if (prefersReducedMotion) {
          stopAutoplay();
          resetProgressBar();
        } else {
          restartAutoplay();
        }
      });
    }

    // Start with the first slide active and fire autoplay once
    // everything is wired up.
    setActiveSlide(0, false);
  }

  // Auto-init all carousels on the page once the DOM is ready.
  document.addEventListener('DOMContentLoaded', function () {
    var roots = document.querySelectorAll('[data-sthc-amenities-carousel]');
    toArray(roots).forEach(initCarousel);
  });
})();
