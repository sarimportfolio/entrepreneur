// ============================================================
//  Entrepreneur Portal — Main JavaScript
// ============================================================

// ── Sidebar toggle (mobile) ──────────────────────────────────
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('sidebarOverlay');

function openSidebar() {
  sidebar?.classList.add('open');
  overlay?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  sidebar?.classList.remove('open');
  overlay?.classList.remove('open');
  document.body.style.overflow = '';
}

hamburger?.addEventListener('click', openSidebar);
overlay?.addEventListener('click', closeSidebar);

// Close sidebar on nav item click (mobile)
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth <= 900) closeSidebar();
  });
});

// ── Tabs ────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.dataset.tab;
    const parent = btn.closest('.tabs-wrapper') || document;

    parent.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    parent.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

    btn.classList.add('active');
    parent.querySelector('#' + target)?.classList.add('active');
  });
});

// ── Modals ──────────────────────────────────────────────────
function openModal(id) {
  const overlay = document.getElementById(id);
  overlay?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  overlay?.classList.remove('open');
  document.body.style.overflow = '';
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
});

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
    });
    document.body.style.overflow = '';
  }
});

// ── Flash auto-dismiss ───────────────────────────────────────
document.querySelectorAll('.flash').forEach(flash => {
  setTimeout(() => {
    flash.style.transition = 'opacity .4s';
    flash.style.opacity    = '0';
    setTimeout(() => flash.remove(), 400);
  }, 4000);
});

// ── Mark message read on open ────────────────────────────────
function markRead(msgId) {
  fetch(`${window.BASE_URL}/includes/mark_read.php?id=${msgId}`)
    .catch(() => {});
}

// ── Confirm delete ───────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm || 'Are you sure?')) {
      e.preventDefault();
    }
  });
});

// ── File input label update ──────────────────────────────────
document.querySelectorAll('.file-input-wrapper input[type=file]').forEach(input => {
  input.addEventListener('change', () => {
    const label = input.closest('.file-input-wrapper')?.querySelector('.file-label');
    if (label && input.files.length) {
      label.textContent = input.files[0].name;
    }
  });
});
