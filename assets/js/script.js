(function () {
  'use strict';

  /* ---------- Navbar scroll state ---------- */
  var navbar = document.getElementById('navbar');
  var onScroll = function () {
    if (window.scrollY > 30) navbar.classList.add('scrolled');
    else navbar.classList.remove('scrolled');
  };
  document.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile menu ---------- */
  var navToggle = document.getElementById('navToggle');
  navToggle.addEventListener('click', function () {
    navbar.classList.toggle('mobile-open');
    navToggle.classList.toggle('open');
    document.body.classList.toggle('no-scroll');
  });
  document.querySelectorAll('.nav-links a').forEach(function (link) {
    link.addEventListener('click', function () {
      navbar.classList.remove('mobile-open');
      navToggle.classList.remove('open');
      document.body.classList.remove('no-scroll');
    });
  });

  /* ---------- Active nav link on scroll ---------- */
  var sections = document.querySelectorAll('section[id]');
  var navAnchors = document.querySelectorAll('.nav-links a');
  var setActive = function () {
    var pos = window.scrollY + 140;
    sections.forEach(function (sec) {
      var top = sec.offsetTop, bottom = top + sec.offsetHeight;
      var id = sec.getAttribute('id');
      var link = document.querySelector('.nav-links a[href="#' + id + '"]');
      if (!link) return;
      if (pos >= top && pos < bottom) {
        navAnchors.forEach(function (a) { a.classList.remove('active'); });
        link.classList.add('active');
      }
    });
  };
  document.addEventListener('scroll', setActive, { passive: true });
  setActive();

  /* ---------- Reveal on scroll ---------- */
  var revealEls = document.querySelectorAll('.reveal');
  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('in');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  revealEls.forEach(function (el) { revealObserver.observe(el); });

  /* ---------- Animate skill bars when visible ---------- */
  var skillBars = document.querySelectorAll('.skill-bar i');
  var barObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        var el = entry.target;
        el.style.width = el.getAttribute('data-width') + '%';
        barObserver.unobserve(el);
      }
    });
  }, { threshold: 0.4 });
  skillBars.forEach(function (el) { barObserver.observe(el); });

  /* ---------- Back to top ---------- */
  var backToTop = document.getElementById('backToTop');
  backToTop.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ---------- Footer year ---------- */
  document.getElementById('year').textContent = new Date().getFullYear();

  /* ---------- Contact form ---------- */
  var form = document.getElementById('contactForm');
  var submitBtn = document.getElementById('submitBtn');
  var statusBox = document.getElementById('formStatus');
  var statusIcon = document.getElementById('formStatusIcon');
  var statusText = document.getElementById('formStatusText');

  var ICONS = {
    ok: '<path d="M20 6L9 17l-5-5"/>',
    bad: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>'
  };

  function showStatus(type, message) {
    statusBox.classList.remove('ok', 'bad');
    statusBox.classList.add('show', type);
    statusIcon.innerHTML = ICONS[type];
    statusText.textContent = message;
  }

  function hideStatus() {
    statusBox.classList.remove('show', 'ok', 'bad');
  }

  function setFieldError(fieldId, message) {
    var field = document.getElementById(fieldId);
    field.classList.toggle('error', !!message);
    field.querySelector('.field-err').textContent = message || '';
  }

  function validate() {
    var name = form.name.value.trim();
    var email = form.email.value.trim();
    var subject = form.subject.value.trim();
    var message = form.message.value.trim();
    var valid = true;

    if (name.length < 2) { setFieldError('field-name', 'Please enter your name.'); valid = false; }
    else setFieldError('field-name', '');

    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRe.test(email)) { setFieldError('field-email', 'Please enter a valid email.'); valid = false; }
    else setFieldError('field-email', '');

    if (subject.length < 2) { setFieldError('field-subject', 'Please enter a subject.'); valid = false; }
    else setFieldError('field-subject', '');

    if (message.length < 10) { setFieldError('field-message', 'Message should be at least 10 characters.'); valid = false; }
    else setFieldError('field-message', '');

    return valid;
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    hideStatus();

    if (!validate()) return;

    var formData = new FormData(form);

    submitBtn.classList.add('loading');
    submitBtn.setAttribute('disabled', 'disabled');

    fetch('contact.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          showStatus('ok', data.message || 'Message sent successfully! I will get back to you soon.');
          form.reset();
        } else {
          showStatus('bad', data.message || 'Something went wrong. Please try again.');
        }
      })
      .catch(function () {
        showStatus('bad', 'Could not send message. Please check your connection and try again.');
      })
      .finally(function () {
        submitBtn.classList.remove('loading');
        submitBtn.removeAttribute('disabled');
      });
  });
})();
