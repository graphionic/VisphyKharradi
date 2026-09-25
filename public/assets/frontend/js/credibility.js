/**
 * Ftpreneur — Section 02: Trust + Credibility ("The Record Arena")
 * Scope: .credibility-section only
 */

document.addEventListener('DOMContentLoaded', () => {
  const section = document.querySelector('.credibility-section');
  if (!section) return;

  const nodes = section.querySelectorAll('.credibility__node');
  const railItems = section.querySelectorAll('.credibility__rail-item');
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Metric mapping for signature performance rail
  const nodeMetricMap = {
    '01': 'DISCIPLINE',
    '02': 'STRENGTH',
    '03': 'ENDURANCE',
    '04': 'CONTROL'
  };

  /**
   * Activate specific record node & corresponding performance rail
   */
  function activateNode(targetNode) {
    const nodeIndex = targetNode.getAttribute('data-node');
    const targetMetric = nodeMetricMap[nodeIndex];

    section.classList.add('has-active-node');
    nodes.forEach(n => n.classList.remove('is-active'));
    targetNode.classList.add('is-active');

    // Rail highlighting
    railItems.forEach(item => {
      if (item.getAttribute('data-metric') === targetMetric) {
        item.classList.add('is-active');
      } else {
        item.classList.remove('is-active');
      }
    });
  }

  /**
   * Reset active state back to default neutral state
   */
  function resetNodes() {
    section.classList.remove('has-active-node');
    nodes.forEach(n => n.classList.remove('is-active'));
    railItems.forEach(item => item.classList.remove('is-active'));
  }

  // Node hover & focus interactions
  nodes.forEach(node => {
    node.addEventListener('mouseenter', () => activateNode(node));
    node.addEventListener('mouseleave', () => resetNodes());

    node.addEventListener('focus', () => activateNode(node));
    node.addEventListener('blur', () => resetNodes());
  });

  // Entrance Observer & Optional Single Pulse Sequence
  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    section.classList.add('is-visible');
    return;
  }

  let hasSequenced = false;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        section.classList.add('is-visible');

        // Optional subtle entrance sequence (01 -> 02 -> 03 -> 04) ONCE
        if (!hasSequenced) {
          hasSequenced = true;
          runEntranceSequence();
        }

        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px'
  });

  observer.observe(section);

  function runEntranceSequence() {
    const sequenceArray = Array.from(nodes);
    let delay = 600;

    sequenceArray.forEach((node, index) => {
      setTimeout(() => {
        // Only trigger if user is not manually hovering a node
        if (!section.matches(':hover')) {
          activateNode(node);
        }
      }, delay + (index * 300));
    });

    // Reset back to interactive default after sequence completes
    setTimeout(() => {
      if (!section.matches(':hover')) {
        resetNodes();
      }
    }, delay + (sequenceArray.length * 300) + 400);
  }
});
