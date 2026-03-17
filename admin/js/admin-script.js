/**
 * Mason Construction Services Inc.
 * Admin Dashboard Script
 */

(function () {
  'use strict';

  /* ── API base paths ────────────────────────────────────────────── */
  var API = {
    contacts:  '../api/get-contacts.php',
    update:    '../api/update-contact.php',
    analytics: '../api/analytics.php',
    logout:    '../api/logout.php',
    login:     '../api/login.php',
  };

  /* ── State ─────────────────────────────────────────────────────── */
  var state = {
    page:    0,
    limit:   20,
    search:  '',
    status:  '',
    total:   0,
    charts:  {},
  };

  /* ── Init ──────────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    checkAuth();
    initNav();
    initMobileMenu();
    initLogout();
    initFilters();
    setPageDate();
    loadDashboard();
  });

  /* ── Auth check ────────────────────────────────────────────────── */
  function checkAuth() {
    fetch(API.contacts + '?limit=1', { credentials: 'include' })
      .then(function (r) {
        if (r.status === 401) {
          window.location.replace('index.html');
        }
        return r.json();
      })
      .then(function (data) {
        // Populate admin name from session (fallback to generic)
        var name = (data && data.admin_name) ? data.admin_name : 'Admin';
        var user = (data && data.admin_username) ? data.admin_username : '';
        setAdminInfo(name, user);
      })
      .catch(function () {
        window.location.replace('index.html');
      });
  }

  function setAdminInfo(name, username) {
    var nameEl     = document.getElementById('adminName');
    var userEl     = document.getElementById('adminUsername');
    var avatarEl   = document.getElementById('adminAvatar');
    if (nameEl)   nameEl.textContent   = name;
    if (userEl)   userEl.textContent   = '@' + username;
    if (avatarEl) avatarEl.textContent = name.charAt(0).toUpperCase();
  }

  /* ── Navigation ────────────────────────────────────────────────── */
  function initNav() {
    var links = document.querySelectorAll('[data-section]');
    links.forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        showSection(this.dataset.section);
        links.forEach(function (l) { l.classList.remove('active'); });
        this.classList.add('active');
        // Close sidebar on mobile
        document.getElementById('sidebar').classList.remove('open');
      });
    });
  }

  window.showSection = function (name) {
    ['dashboard', 'contacts', 'analytics'].forEach(function (s) {
      var el = document.getElementById('section-' + s);
      if (el) el.style.display = (s === name) ? '' : 'none';
    });
    document.getElementById('pageTitle').textContent =
      name.charAt(0).toUpperCase() + name.slice(1);

    // Update sidebar active link
    var links = document.querySelectorAll('[data-section]');
    links.forEach(function (l) {
      l.classList.toggle('active', l.dataset.section === name);
    });

    if (name === 'contacts')  loadContacts();
    if (name === 'analytics') loadAnalytics();
  };

  /* ── Mobile menu ────────────────────────────────────────────────── */
  function initMobileMenu() {
    var btn     = document.getElementById('mobileMenuBtn');
    var sidebar = document.getElementById('sidebar');
    if (btn) {
      btn.addEventListener('click', function () {
        sidebar.classList.toggle('open');
      });
    }
  }

  /* ── Logout ─────────────────────────────────────────────────────── */
  function initLogout() {
    var btn = document.getElementById('logoutBtn');
    if (btn) {
      btn.addEventListener('click', function () {
        fetch(API.logout, { method: 'POST', credentials: 'include' })
          .finally(function () {
            window.location.replace('index.html');
          });
      });
    }
  }

  /* ── Date display ───────────────────────────────────────────────── */
  function setPageDate() {
    var el = document.getElementById('pageDate');
    if (el) {
      el.textContent = new Date().toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
      });
    }
  }

  /* ── Dashboard load ─────────────────────────────────────────────── */
  function loadDashboard() {
    fetch(API.analytics, { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) return;
        var t = data.data.totals || {};
        setText('statTotal',      t.total      || 0);
        setText('statNew',        t.new_count  || 0);
        setText('statInProgress', t.in_progress_count || 0);
        setText('statResolved',   t.resolved_count    || 0);
        setText('statSpam',       t.spam_count        || 0);

        // Show new badge
        var nb = document.getElementById('newBadge');
        if (nb && t.new_count > 0) {
          nb.textContent    = t.new_count;
          nb.style.display  = 'inline-block';
        }
      })
      .catch(function () {});

    // Load recent submissions (latest 5)
    fetch(API.contacts + '?limit=5&offset=0', { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        renderRecentTable(data.data || []);
      })
      .catch(function () {});
  }

  function renderRecentTable(rows) {
    var tbody = document.getElementById('recentTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:32px;color:#718096;">No submissions yet.</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (r) {
      return '<tr>' +
        '<td>' + esc(r.id) + '</td>' +
        '<td>' + esc(r.name) + '</td>' +
        '<td>' + esc(r.email) + '</td>' +
        '<td>' + esc(r.subject || '–') + '</td>' +
        '<td>' + badge(r.status) + '</td>' +
        '<td>' + formatDate(r.submitted_at) + '</td>' +
      '</tr>';
    }).join('');
  }

  /* ── Contacts section ────────────────────────────────────────────── */
  function initFilters() {
    var search = document.getElementById('searchInput');
    var filter = document.getElementById('statusFilter');
    var refresh = document.getElementById('refreshBtn');
    var debounceTimer;

    if (search) {
      search.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          state.search = search.value.trim();
          state.page   = 0;
          loadContacts();
        }, 400);
      });
    }

    if (filter) {
      filter.addEventListener('change', function () {
        state.status = filter.value;
        state.page   = 0;
        loadContacts();
      });
    }

    if (refresh) {
      refresh.addEventListener('click', function () {
        loadContacts();
      });
    }
  }

  function loadContacts() {
    var params = new URLSearchParams({
      limit:  state.limit,
      offset: state.page * state.limit,
      search: state.search,
      status: state.status,
    });

    var tbody = document.getElementById('contactsTableBody');
    if (tbody) {
      tbody.innerHTML = '<tr class="loading-row"><td colspan="8">Loading…</td></tr>';
    }

    fetch(API.contacts + '?' + params.toString(), { credentials: 'include' })
      .then(function (r) {
        if (r.status === 401) { window.location.replace('index.html'); }
        return r.json();
      })
      .then(function (data) {
        state.total = data.total || 0;
        renderContactsTable(data.data || []);
        renderPagination();
      })
      .catch(function () {
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#e53e3e;">Failed to load contacts.</td></tr>';
      });
  }

  function renderContactsTable(rows) {
    var tbody = document.getElementById('contactsTableBody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:#718096;">No submissions found.</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (r) {
      return '<tr>' +
        '<td>' + esc(r.id) + '</td>' +
        '<td>' + esc(r.name) + '</td>' +
        '<td><a href="mailto:' + esc(r.email) + '">' + esc(r.email) + '</a></td>' +
        '<td>' + esc(r.phone || '–') + '</td>' +
        '<td>' + esc(r.subject || '–') + '</td>' +
        '<td>' + badge(r.status) + '</td>' +
        '<td style="white-space:nowrap;">' + formatDate(r.submitted_at) + '</td>' +
        '<td><button class="btn btn-secondary btn-sm" onclick="openModal(' + JSON.stringify(r) + ')">View</button></td>' +
      '</tr>';
    }).join('');
  }

  function renderPagination() {
    var info  = document.getElementById('paginationInfo');
    var btns  = document.getElementById('paginationBtns');
    if (!info || !btns) return;

    var start  = state.page * state.limit + 1;
    var end    = Math.min((state.page + 1) * state.limit, state.total);
    info.textContent = 'Showing ' + (state.total ? start : 0) + '–' + end + ' of ' + state.total;

    var pages  = Math.ceil(state.total / state.limit);
    var html   = '';
    html += '<button ' + (state.page === 0 ? 'disabled' : '') + ' onclick="goPage(' + (state.page - 1) + ')">← Prev</button>';
    for (var i = 0; i < Math.min(pages, 7); i++) {
      html += '<button class="' + (i === state.page ? 'active' : '') + '" onclick="goPage(' + i + ')">' + (i + 1) + '</button>';
    }
    if (pages > 7) html += '<button disabled>…</button>';
    html += '<button ' + (state.page >= pages - 1 ? 'disabled' : '') + ' onclick="goPage(' + (state.page + 1) + ')">Next →</button>';
    btns.innerHTML = html;
  }

  window.goPage = function (n) {
    state.page = n;
    loadContacts();
  };

  /* ── Contact modal ───────────────────────────────────────────────── */
  window.openModal = function (contact) {
    var overlay = document.getElementById('contactModal');
    var body    = document.getElementById('modalBody');
    var footer  = document.getElementById('modalFooter');
    if (!overlay || !body || !footer) return;

    body.innerHTML =
      detailRow('Name',       contact.name) +
      detailRow('Email',      '<a href="mailto:' + esc(contact.email) + '">' + esc(contact.email) + '</a>') +
      detailRow('Phone',      contact.phone || '–') +
      detailRow('Subject',    contact.subject || '–') +
      detailRow('Submitted',  formatDate(contact.submitted_at)) +
      detailRow('Status',     badge(contact.status)) +
      detailRow('Message',    '<div style="white-space:pre-wrap;background:#f7fafc;padding:12px;border-radius:6px;margin-top:4px;">' + esc(contact.message) + '</div>') +
      '<div>' +
        '<label class="form-group" style="display:block;margin-top:16px;">' +
          '<span style="font-size:.8rem;font-weight:600;color:#718096;text-transform:uppercase;letter-spacing:.4px;">Admin Notes</span>' +
          '<textarea id="modalNotes" rows="3" style="width:100%;margin-top:6px;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;">' +
            esc(contact.admin_notes || '') +
          '</textarea>' +
        '</label>' +
      '</div>';

    footer.innerHTML =
      '<select id="modalStatus" style="padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;">' +
        statusOption('new',         contact.status) +
        statusOption('read',        contact.status) +
        statusOption('in_progress', contact.status) +
        statusOption('resolved',    contact.status) +
        statusOption('spam',        contact.status) +
      '</select>' +
      '<button class="btn btn-primary btn-sm" onclick="saveContact(' + contact.id + ')">Save</button>' +
      '<button class="btn btn-secondary btn-sm" onclick="closeModal()">Cancel</button>';

    overlay.classList.add('open');
  };

  window.saveContact = function (id) {
    var status = document.getElementById('modalStatus').value;
    var notes  = document.getElementById('modalNotes').value;

    fetch(API.update, {
      method:      'POST',
      headers:     { 'Content-Type': 'application/json' },
      credentials: 'include',
      body:        JSON.stringify({ id: id, status: status, admin_notes: notes }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          showToast('Contact updated successfully.', 'success');
          closeModal();
          loadContacts();
          loadDashboard();
        } else {
          showToast(data.message || 'Update failed.', 'error');
        }
      })
      .catch(function () {
        showToast('Connection error. Please try again.', 'error');
      });
  };

  window.closeModal = function () {
    var overlay = document.getElementById('contactModal');
    if (overlay) overlay.classList.remove('open');
  };

  document.getElementById('modalClose').addEventListener('click', closeModal);
  document.getElementById('contactModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });

  /* ── Analytics ───────────────────────────────────────────────────── */
  function loadAnalytics() {
    fetch(API.analytics, { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) return;
        renderCharts(res.data);
      })
      .catch(function () {});
  }

  function renderCharts(data) {
    renderDailyChart(data.daily   || []);
    renderStatusChart(data.totals || {});
    renderMonthlyChart(data.monthly || []);
  }

  function renderDailyChart(rows) {
    var ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    if (state.charts.daily) state.charts.daily.destroy();
    state.charts.daily = new Chart(ctx, {
      type: 'bar',
      data: {
        labels:   rows.map(function (r) { return r.day; }),
        datasets: [{
          label:           'Submissions',
          data:            rows.map(function (r) { return r.count; }),
          backgroundColor: 'rgba(233,69,96,.7)',
          borderColor:     'rgba(233,69,96,1)',
          borderWidth:     1,
        }],
      },
      options: { responsive: true, plugins: { legend: { display: false } } },
    });
  }

  function renderStatusChart(totals) {
    var ctx = document.getElementById('statusChart');
    if (!ctx) return;
    if (state.charts.status) state.charts.status.destroy();
    state.charts.status = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['New', 'Read', 'In Progress', 'Resolved', 'Spam'],
        datasets: [{
          data: [
            totals.new_count         || 0,
            totals.read_count        || 0,
            totals.in_progress_count || 0,
            totals.resolved_count    || 0,
            totals.spam_count        || 0,
          ],
          backgroundColor: ['#4299e1','#48bb78','#f5a623','#9f7aea','#e53e3e'],
        }],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
  }

  function renderMonthlyChart(rows) {
    var ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    if (state.charts.monthly) state.charts.monthly.destroy();
    state.charts.monthly = new Chart(ctx, {
      type: 'line',
      data: {
        labels:   rows.map(function (r) { return r.month; }),
        datasets: [{
          label:           'Submissions',
          data:            rows.map(function (r) { return r.count; }),
          fill:            true,
          backgroundColor: 'rgba(233,69,96,.15)',
          borderColor:     'rgba(233,69,96,1)',
          tension:         .4,
          pointRadius:     4,
        }],
      },
      options: { responsive: true },
    });
  }

  /* ── Toast ───────────────────────────────────────────────────────── */
  function showToast(msg, type) {
    var container = document.getElementById('toastContainer');
    if (!container) return;
    var toast = document.createElement('div');
    toast.className = 'toast ' + (type || '');
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 3500);
  }

  /* ── Helpers ─────────────────────────────────────────────────────── */
  function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function setText(id, val) {
    var el = document.getElementById(id);
    if (el) el.textContent = val;
  }

  function formatDate(dateStr) {
    if (!dateStr) return '–';
    var d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
           ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  }

  function badge(status) {
    return '<span class="badge badge-' + esc(status) + '">' + esc(status) + '</span>';
  }

  function detailRow(label, value) {
    return '<div class="detail-row"><label>' + label + '</label><div class="value">' + value + '</div></div>';
  }

  function statusOption(value, current) {
    var label = value.replace('_', ' ');
    return '<option value="' + value + '"' + (value === current ? ' selected' : '') + '>' +
      label.charAt(0).toUpperCase() + label.slice(1) + '</option>';
  }

})();
