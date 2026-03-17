/**
 * Mason Construction Services Inc.
 * Contact Form API Integration
 * Handles form submission to the backend PHP API
 */

(function () {
  'use strict';

  // API endpoint (relative path works on same domain)
  var API_URL = '/api/submit-contact.php';

  var form         = document.getElementById('contactForm');
  var submitBtn    = form ? form.querySelector('button[type="submit"]') : null;
  var successMsg   = document.getElementById('formSuccess');
  var errorMsg     = document.getElementById('formError');

  if (!form) return;

  // ── Validation helpers ──────────────────────────────────────────────────────

  function showFieldError(id, show) {
    var el = document.getElementById(id);
    if (el) el.style.display = show ? 'block' : 'none';
  }

  function validateForm(data) {
    var valid = true;

    if (!data.name || data.name.length < 2 || data.name.length > 100) {
      showFieldError('nameError', true);
      valid = false;
    } else {
      showFieldError('nameError', false);
    }

    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!data.email || !emailRe.test(data.email)) {
      showFieldError('emailError', true);
      valid = false;
    } else {
      showFieldError('emailError', false);
    }

    if (!data.message || data.message.length < 10) {
      showFieldError('messageError', true);
      valid = false;
    } else {
      showFieldError('messageError', false);
    }

    return valid;
  }

  // ── Banner helpers ──────────────────────────────────────────────────────────

  function showBanner(el, message) {
    if (!el) return;
    el.textContent = message;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function hideBanners() {
    if (successMsg) successMsg.style.display = 'none';
    if (errorMsg)   errorMsg.style.display   = 'none';
  }

  // ── Form submit ─────────────────────────────────────────────────────────────

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    hideBanners();

    var data = {
      name:    (form.querySelector('[name="w3lName"]')    || {}).value || '',
      email:   (form.querySelector('[name="w3lSender"]')  || {}).value || '',
      phone:   (form.querySelector('[name="w3lPhone"]')   || {}).value || '',
      subject: (form.querySelector('[name="w3lSubject"]') || {}).value || 'General Inquiry',
      message: (form.querySelector('[name="w3lMessage"]') || {}).value || '',
    };

    if (!validateForm(data)) return;

    // Disable submit button while sending
    if (submitBtn) {
      submitBtn.disabled    = true;
      submitBtn.textContent = 'Sending…';
    }

    fetch(API_URL, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      body:        JSON.stringify(data),
      credentials: 'same-origin',
    })
      .then(function (res) {
        return res.json().then(function (json) {
          return { status: res.status, body: json };
        });
      })
      .then(function (response) {
        if (response.body.success) {
          showBanner(successMsg, response.body.message ||
            'Thank you! Your message has been received. We\'ll be in touch soon.');
          form.reset();
        } else {
          showBanner(errorMsg, response.body.message ||
            'Something went wrong. Please try again or call us at +(347) 933-0867.');
        }
      })
      .catch(function () {
        showBanner(errorMsg,
          'Unable to send your message. Please check your connection or call us at +(347) 933-0867.');
      })
      .finally(function () {
        if (submitBtn) {
          submitBtn.disabled    = false;
          submitBtn.textContent = 'Submit Message';
        }
      });
  });
})();
