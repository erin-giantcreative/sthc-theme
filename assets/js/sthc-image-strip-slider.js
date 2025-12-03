/**
 * STHC Image Strip Slider
 *
 * Behaviour:
 * - Images sit in a horizontal row inside a track.
 * - The viewport hides overflow.
 * - Left and right buttons shift the track by one image.
 * - Status text tells screen readers which image is at the left edge.
 */

(function () {
  'use strict';

  function toArray(nodeList) {
    return Array.prototype.slice.call(nodeList || []);
  }

  /**
   * Set up one slider instance.
   *
   * @param {HTMLElement} root
   */
  function initSlider(root) {
    if (!root) return;

    var viewport = root.querySelector('.sthc-image-strip-slider__viewport');
    var track = root.querySelector('[data-gallery-track]');
    var items = toArray(root.querySelectorAll('[data-gallery-item]'));
    var prevBtn = root.querySelector('[data-gallery-prev]');
    var nextBtn = root.querySelector('[data-gallery-next]');
    var status = root.querySelector('.sthc-image-strip-slider__status');

    // If the structure is missing, do nothing.
    if (!viewport || !track || items.length === 0 || !prevBtn || !nextBtn) {
      return;
    }

    // Index of the first visible item.
    var index = 0;

    // Item width (including gap) in pixels for the current viewport.
    var itemWidth = 0;

    // How many items fit in the viewport at once.
    var visibleCount = 1;

    /**
     * Read numeric gap value from the CSS gap property.
     */
    function getGapValue() {
      var styles = window.getComputedStyle(track);
      var gap = styles.getPropertyValue('column-gap') || styles.getPropertyValue('gap');
      var numeric = parseFloat(gap);
      return isNaN(numeric) ? 0 : numeric;
    }

    /**
     * Measure geometry whenever the viewport changes size.
     * This keeps movement correct at each breakpoint.
     */
    function measure() {
      if (!items[0]) return;

      var gap = getGapValue();
      itemWidth = items[0].offsetWidth + gap;

      var viewportWidth = viewport.offsetWidth;
      visibleCount = itemWidth > 0 ? Math.max(Math.round(viewportWidth / itemWidth), 1) : 1;

      applyTransform(true);
    }

    /**
     * Determine the maximum index based on how many items are visible.
     * For looping, we still need this to calculate how far the track moves,
     * but we no longer use it to disable arrows.
     */
    function getMaxIndex() {
      return Math.max(items.length - visibleCount, 0);
    }

    /**
     * Apply translateX based on the current index.
     *
     * @param {boolean} skipStatus Skip status update when only syncing layout.
     */
    function applyTransform(skipStatus) {
      var offset = -(index * itemWidth);
      track.style.transform = 'translateX(' + offset + 'px)';
      updateButtons();
      if (!skipStatus) {
        updateStatus();
      }
    }

    /**
     * Enable or disable arrow buttons based on index bounds.
     */
    function updateButtons() {
      // Looping: arrows are always enabled
      prevBtn.disabled = false;
      nextBtn.disabled = false;

    }

    /**
     * Update the live status text for assistive tech.
     */
    function updateStatus() {
      if (!status) return;
      var current = Math.min(index + 1, items.length);
      var total = items.length;
      status.textContent = 'Showing image ' + current + ' of ' + total;
    }

    /**
     * Prev now wraps around.
     */
    function goPrev() {
      var maxIndex = getMaxIndex();
      index -= 1;
      if (index < 0) {
        index = maxIndex; // Wrap to end
      }
      applyTransform();
    }

    /**
     * Next now wraps around.
     */
    function goNext() {
      var maxIndex = getMaxIndex();
      index += 1;
      if (index > maxIndex) {
        index = 0; // Wrap to start
      }
      applyTransform();
    }


    prevBtn.addEventListener('click', goPrev);
    nextBtn.addEventListener('click', goNext);

    // Arrow keys also move the strip when focus is inside the component.
    root.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goPrev();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        goNext();
      }
    });

    // Measure on load and whenever the viewport resizes.
    window.addEventListener('load', measure);
    window.addEventListener('resize', measure);

    // First measure and status update.
    measure();
    updateStatus();
  }

  // Find every slider on the page and initialise it.
  document.addEventListener('DOMContentLoaded', function () {
    var roots = document.querySelectorAll('[data-sthc-image-strip-slider]');
    toArray(roots).forEach(initSlider);
  });
})();
