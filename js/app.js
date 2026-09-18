// ============================================================
// APP.JS — Utilidades + CRUD de Reportes (fetch → PHP)
// ============================================================

// ── REPORTES ─────────────────────────────────────────────────
const REPORTES = {
  async _get(action, params = {}) {
    const qs = new URLSearchParams({ action, ...params });
    const res = await fetch(`api/reportes.php?${qs}`, { credentials: 'include' });
    return res.json();
  },

  async _post(action, data) {
    const res = await fetch(`api/reportes.php?action=${action}`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    return res.json();
  },

  // Listado público (con filtros opcionales)
  async getPublicos(params = {}) {
    const r = await this._get('listar', params);
    return r.ok ? r.reportes : [];
  },

  // Mis reportes
  async getMios() {
    const r = await this._get('mis_reportes');
    return r.ok ? r.reportes : [];
  },

  // Todos los reportes (admin)
  async getTodos() {
    const r = await this._get('todos');
    return r.ok ? r.reportes : [];
  },

  // Estadísticas (admin)
  async getStats() {
    const r = await this._get('stats');
    return r.ok ? r : null;
  },

  // Crear con imagen (usa FormData para multipart)
  async crear(data, imagenFile = null) {
    const form = new FormData();
    ['titulo','descripcion','tipo','gravedad','ubicacion'].forEach(k => {
      if (data[k] !== undefined) form.append(k, data[k]);
    });
    if (imagenFile) form.append('imagen', imagenFile);

    const res = await fetch('api/reportes.php?action=crear', {
      method: 'POST',
      credentials: 'include',
      body: form
    });
    return res.json();
  },

  // Actualizar
  async actualizar(id, data) {
    return this._post('actualizar', { id, ...data });
  },

  // Eliminar
  async eliminar(id) {
    return this._post('eliminar', { id });
  }
};

// ── USUARIOS (admin) ──────────────────────────────────────────
const USUARIOS = {
  async listar() {
    const res = await fetch('api/usuarios.php?action=listar', { credentials: 'include' });
    const r = await res.json();
    return r.ok ? r.usuarios : [];
  },
  async toggleActivo(id) {
    const res = await fetch('api/usuarios.php?action=toggle_activo', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    return res.json();
  }
};

// ── APP HELPERS ───────────────────────────────────────────────
const APP = {
  tipoInfo: {
    derrumbe:  { label: 'Derrumbe',          emoji: '⛰️',  cls: 'tipo-derrumbe'  },
    incendio:  { label: 'Incendio',           emoji: '🔥',  cls: 'tipo-incendio'  },
    robo:      { label: 'Robo',               emoji: '🚨',  cls: 'tipo-robo'      },
    choque:    { label: 'Choque',             emoji: '🚗',  cls: 'tipo-choque'    },
    vandalismo:{ label: 'Vandalismo',         emoji: '🚫',  cls: 'tipo-vandalismo'},
    violencia: { label: 'Violencia',          emoji: '⚠️',  cls: 'tipo-violencia' },
    emergencia:{ label: 'Emergencia médica',  emoji: '🏥',  cls: 'tipo-emergencia'},
    otro:      { label: 'Otro',               emoji: '📋',  cls: 'tipo-otro'      }
  },

  gravedadInfo: {
    alta:  { label: 'Alta',  cls: 'gravedad-alta',  ico: '🔴' },
    media: { label: 'Media', cls: 'gravedad-media', ico: '🟡' },
    baja:  { label: 'Baja',  cls: 'gravedad-baja',  ico: '🟢' }
  },

  formatFecha(iso) {
    const d = new Date(iso);
    return d.toLocaleDateString('es-CO', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  },

  fechaRelativa(iso) {
    const diff = (Date.now() - new Date(iso)) / 1000;
    if (diff < 60)    return 'Hace un momento';
    if (diff < 3600)  return `Hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Hace ${Math.floor(diff / 3600)}h`;
    return `Hace ${Math.floor(diff / 86400)} días`;
  },

  toast(msg, type = 'info') {
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${icons[type] || 'ℹ️'}</span> ${msg}`;
    const container = document.getElementById('toast-container');
    if (container) container.appendChild(el);
    setTimeout(() => el.remove(), 3500);
  },

  // Renderizar navbar según sesión
  renderNavbar(activeLink, session) {
    const navEl = document.getElementById('main-nav');
    if (!navEl) return;
    const links = [
      { href: 'index.html',    label: '🏠 Inicio' },
      { href: 'feed.html',     label: '📋 Reportes' },
      { href: 'alcaldia.html', label: '🏛️ Alcaldía' },
      { href: 'policia.html',  label: '👮 Policía' },
      { href: 'hospital.html', label: '🏥 Hospital' },
    ];
    const linksHTML = links.map(l =>
      `<li><a href="${l.href}" ${activeLink === l.href ? 'class="active"' : ''}>${l.label}</a></li>`
    ).join('');

    let actionsHTML;
    if (session) {
      const dashLink = session.rol === 'admin' ? 'admin.html' : 'dashboard.html';
      actionsHTML = `
        <div class="navbar-user">
          <div class="user-avatar">${session.avatar || '👤'}</div>
          <span>${session.nombre}</span>
          <a href="${dashLink}" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.5)">Panel</a>
          <button onclick="AUTH.logout()" class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:white">Salir</button>
        </div>`;
    } else {
      actionsHTML = `
        <a href="login.html" class="btn btn-outline btn-sm" style="color:white;border-color:rgba(255,255,255,0.5)">Iniciar Sesión</a>
        <a href="registro.html" class="btn btn-primary btn-sm" style="background:rgba(255,255,255,0.2)">Registrarse</a>`;
    }

    navEl.innerHTML = `
      <div class="navbar-brand">
        <div class="shield-icon">🛡️</div>
        <div>
          <div>SEGURIDAD GUACHETÁ</div>
          <div style="font-size:0.65em;font-weight:400;opacity:0.8">Sistema de Gestión de Seguridad</div>
        </div>
      </div>
      <ul class="navbar-links">${linksHTML}</ul>
      <div class="navbar-actions">${actionsHTML}</div>`;
  },

  // Renderizar tarjeta de reporte
  renderReporteCard(r, showActions = false, currentUser = null) {
    const tipo = this.tipoInfo[r.tipo] || this.tipoInfo.otro;
    const grav = this.gravedadInfo[r.gravedad] || this.gravedadInfo.baja;

    // URL de imagen (del servidor o placeholder)
    const imgHTML = r.imagen_url
      ? `<img class="reporte-card-img" src="${r.imagen_url}" alt="Imagen del reporte" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
      : '';
    const placeholder = `<div class="reporte-card-img-placeholder" ${r.imagen_url ? 'style=display:none' : ''}>${tipo.emoji}</div>`;

    const canEdit = currentUser && (currentUser.rol === 'admin' || (int = parseInt)(currentUser.id) === parseInt(r.usuario_id));
    const actionsHTML = (showActions && canEdit) ? `
      <div style="display:flex;gap:8px;margin-top:12px">
        <button class="btn btn-outline btn-sm" onclick="abrirEditar(${r.id})">✏️ Editar</button>
        <button class="btn btn-danger btn-sm" onclick="eliminarReporte(${r.id})">🗑️ Eliminar</button>
      </div>` : '';

    const estadoBadge = r.estado === 'resuelto'
      ? '<span class="badge badge-green">✅ Resuelto</span>'
      : r.estado === 'en proceso'
        ? '<span class="badge badge-blue">🔄 En proceso</span>'
        : '<span class="badge badge-red">🔴 Activo</span>';

    return `
      <div class="reporte-card">
        ${imgHTML}${placeholder}
        <div class="reporte-card-body">
          <div>
            <span class="reporte-tipo ${tipo.cls}">${tipo.emoji} ${tipo.label}</span>
            <span class="gravedad-badge ${grav.cls}">${grav.ico} ${grav.label}</span>
            ${estadoBadge}
          </div>
          <h3>${r.titulo}</h3>
          <p>${(r.descripcion||'').length > 120 ? r.descripcion.substring(0,120)+'...' : r.descripcion}</p>
          ${r.ubicacion ? `<p style="margin-top:6px">📍 ${r.ubicacion}</p>` : ''}
          <div class="reporte-meta">
            <span>👤 ${r.usuario_nombre || ''}</span>
            <span>🕒 ${this.fechaRelativa(r.fecha)}</span>
          </div>
          ${actionsHTML}
        </div>
      </div>`;
  }
};
