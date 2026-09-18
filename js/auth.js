// ============================================================
// AUTH.JS — Sistema de Autenticación (fetch → PHP backend)
// ============================================================

const AUTH = {
  _session: null,  // caché en memoria

  // Llamar al backend PHP
  async _call(action, data = null, method = 'GET') {
    const url = `api/auth.php?action=${action}`;
    const opts = { credentials: 'include' };
    if (data !== null) {
      opts.method = 'POST';
      opts.headers = { 'Content-Type': 'application/json' };
      opts.body = JSON.stringify(data);
    } else {
      opts.method = method;
    }
    const res = await fetch(url, opts);
    return res.json();
  },

  // Login
  async login(email, password) {
    const r = await this._call('login', { email, password });
    if (r.ok) this._session = r.user;
    return r;
  },

  // Logout
  async logout() {
    await this._call('logout', {});
    this._session = null;
    window.location.href = 'index.html';
  },

  // Registro
  async register(data) {
    const r = await this._call('register', data);
    if (r.ok) this._session = r.user;
    return r;
  },

  // Obtener sesión actual del servidor
  async getSession() {
    if (this._session) return this._session;
    try {
      const r = await this._call('session');
      if (r.ok) { this._session = r.user; return r.user; }
    } catch(e) {}
    return null;
  },

  // Versión sincrónica segura (usar solo si sabes que getSession() ya se llamó)
  getCachedSession() {
    return this._session;
  },

  // Verificar si hay sesión activa (async)
  async isLoggedIn() {
    return !!(await this.getSession());
  },

  // Inicializar página — carga sesión y llama callback
  async init(callback) {
    await this.getSession();
    if (callback) callback(this._session);
  },

  // Redirigir si no hay sesión
  async requireAuth(redirect = 'login.html') {
    const s = await this.getSession();
    if (!s) { window.location.href = redirect; return null; }
    return s;
  },

  // Redirigir si ya hay sesión
  async redirectIfLoggedIn(to = 'dashboard.html') {
    const s = await this.getSession();
    if (s) window.location.href = to;
  }
};
