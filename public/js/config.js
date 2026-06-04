// js/config.js — VERSION FINALE

const CONFIG = {
    API_URL    : 'http://localhost:8000/api',
    STORAGE_URL: 'http://localhost:8000/storage',
    IA_URL     : 'http://localhost:8001',
    IA_KEY     : 'simmo-secret-key-2026',
}

// const BASE_URL = window.location.origin

// const CONFIG = {
//     API_URL    : BASE_URL + '/api',
//     STORAGE_URL: BASE_URL + '/storage',
//     IA_URL     : window.location.origin,
//     IA_KEY     : 'simmo-secret-key-2026',
// }



// ── TOKEN : agent ET utilisateur ont le même token key ──────
// Admin garde ses propres clés séparées

function getToken()    { return localStorage.getItem('token') }
function getUser()     { try { return JSON.parse(localStorage.getItem('user')) } catch { return null } }
function getRole()     { return localStorage.getItem('role') || getUser()?.role || null }
function estConnecte() { return !!getToken() }
function estAgent()    { return getRole() === 'agent' }
function estAdmin()    { return !!localStorage.getItem('simmo_admin_token') }

function getAdminToken() { return localStorage.getItem('simmo_admin_token') }
function getAdmin()      { try { return JSON.parse(localStorage.getItem('simmo_admin')) } catch { return null } }

//
function sauvegarderSession(token, user) {
    localStorage.setItem('token', token)
    localStorage.setItem('user',  JSON.stringify(user))
    // role toujours propre
    const role = (user.role === 'agent' || user.type === 'agent')
        ? 'agent' : 'utilisateur'
    localStorage.setItem('role', role)
}

function deconnexion() {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('role')
    localStorage.removeItem('redirect_after_login')
    window.location.href = 'index.html'
}

function deconnexionAgent() {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('role')
    localStorage.removeItem('redirect_after_login') 
    window.location.href = 'index.html'
}

// ── HEADERS ─────────────────────────────────────────────────
function headersPublic() {
    return { 'Content-Type': 'application/json', 'Accept': 'application/json' }
}

function headersAuth() {
    return {
        'Content-Type' : 'application/json',
        'Accept'       : 'application/json',
        'Authorization': 'Bearer ' + getToken(),
    }
}

function headersAdmin() {
    return {
        'Content-Type' : 'application/json',
        'Accept'       : 'application/json',
        'Authorization': 'Bearer ' + getAdminToken(),
    }
}

// ── APPEL LARAVEL ────────────────────────────────────────────
async function apiRequest(endpoint, method = 'GET', body = null, auth = false) {
    const headers = auth ? headersAuth() : headersPublic()
    const options = { method, headers }
    if (body && method !== 'GET') options.body = JSON.stringify(body)

    try {
        const res = await fetch(CONFIG.API_URL + endpoint, options)

        if (res.status === 401) {
            if (auth) {
                localStorage.setItem('redirect_after_login', location.href)
                // Rediriger vers le bon login selon le rôle
                window.location.href = estAgent() ? 'login-agent.html' : 'login.html'
            }
            return null
        }

        return await res.json()
    } catch (e) {
        console.error('apiRequest:', endpoint, e.message)
        return null
    }
}

// ── APPEL IA ─────────────────────────────────────────────────
async function appelIA(endpoint, body = {}) {
    try {
        const res = await fetch(CONFIG.IA_URL + '/api' + endpoint, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-API-Key': CONFIG.IA_KEY },
            body   : JSON.stringify(body),
        })
        if (!res.ok) return null
        return await res.json()
    } catch (e) {
        console.warn('IA indisponible:', e.message)
        return null
    }
}

// ── UTILITAIRES ──────────────────────────────────────────────
function imageUrl(chemin) {
    if (!chemin || ['null','undefined','None',''].includes(String(chemin))) {
        return genererPlaceholder('SIMMo')
    }
    if (chemin.startsWith('http')) return chemin
    return CONFIG.STORAGE_URL + '/' + chemin.replace(/^\/+/, '')
}

function genererPlaceholder(texte) {
    try {
        const c = document.createElement('canvas')
        c.width = 400; c.height = 250
        const x = c.getContext('2d')
        x.fillStyle = '#e2e8f0'; x.fillRect(0,0,400,250)
        x.fillStyle = '#64748b'; x.font = 'bold 16px sans-serif'
        x.textAlign = 'center'; x.textBaseline = 'middle'
        x.fillText('🏠  ' + (texte||'SIMMo').substring(0,20), 200, 125)
        return c.toDataURL()
    } catch { return '' }
}

function formatPrix(prix) {
    if (!prix && prix !== 0) return '—'
    return new Intl.NumberFormat('fr-FR').format(Number(prix)) + ' F CFA'
}

function showAlert(message, type, containerId) {
    const el = document.getElementById(containerId || 'alertContainer')
    if (!el) return
    el.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show">
        ${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`
    setTimeout(() => el.querySelector('.alert')?.remove(), 4000)
}

function showSpinner(btnId, texte) {
    const btn = document.getElementById(btnId)
    if (btn) { btn.disabled = true; btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${texte}` }
}

function hideSpinner(btnId, texte) {
    const btn = document.getElementById(btnId)
    if (btn) { btn.disabled = false; btn.innerHTML = texte }
}

function apiFormData(endpoint, formData) {
    return fetch(CONFIG.API_URL + endpoint, {
        method : 'POST',
        headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + getToken() },
        body   : formData,
    }).then(r => r.json())
}

function redirigerApresConnexion() {
    const r = localStorage.getItem('redirect_after_login')
    localStorage.removeItem('redirect_after_login')
    window.location.href = r && !r.includes('login') ? r : (estAgent() ? 'dashboard.html' : 'index.html')
}

function buildCriteresIA(opts = {}) {
    return {
        ville: opts.ville||null, type_bien: opts.type_bien||null,
        type_transaction: opts.type_transaction||null,
        budget_min: opts.budget_min ? parseFloat(opts.budget_min) : null,
        budget_max: opts.budget_max ? parseFloat(opts.budget_max) : null,
        nb_chambres: opts.nb_chambres ? parseInt(opts.nb_chambres) : null,
        surface_min: opts.surface_min ? parseFloat(opts.surface_min) : null,
        meuble: opts.meuble||null, limite: opts.limite||12,
    }
}

// ── NAVBAR ───────────────────────────────────────────────────
function initNavbar() {
    const navAuth      = document.getElementById('navAuth')
    const navDashboard = document.getElementById('navDashboard')
    if (!navAuth) return

    if (estConnecte()) {
        const user     = getUser()
        const initiale = user?.prenom?.[0]?.toUpperCase() || 'U'
        const prenom   = user?.prenom || ''
        if (navDashboard && estAgent()) navDashboard.style.display = 'block'

        navAuth.innerHTML = `
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2"
                type="button" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center
                    justify-content-center fw-bold" style="width:30px;height:30px;font-size:12px">
                    ${initiale}</div>
                <span class="fw-semibold small d-none d-md-inline">${prenom}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item" href="profil.html">
                    <i class="bi bi-person me-2 text-primary"></i>Mon profil</a></li>
                ${!estAgent() ? `<li><a class="dropdown-item" href="mes-favoris.html">
                    <i class="bi bi-heart-fill text-danger me-2"></i>Mes favoris</a></li>
                    ` : ''}
                    
${!estAgent() ? `
<li>
    <a class="dropdown-item" href="chats.html">
        <i class="bi bi-chat-dots me-2 text-primary"></i>
        Mes messages
        <span class="badge bg-danger ms-1 d-none" id="badgeNavMessages"></span>
    </a>
</li>` : ''}
                ${estAgent() ? `
                <li><a class="dropdown-item" href="dashboard.html">
                    <i class="bi bi-grid me-2 text-primary"></i>Dashboard</a></li>
                <li><a class="dropdown-item" href="chats.html">
                    <i class="bi bi-chat-dots me-2 text-primary"></i>Mes messages
                    <span class="badge bg-danger ms-1 d-none" id="badgeNavMessages"></span></a></li>` : ''}
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item text-danger"
                    onclick="${estAgent() ? 'deconnexionAgent()' : 'deconnexion()'}">
                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion</button></li>
            </ul>
        </div>`

        if (estAgent()) chargerBadgeMessages()
    } else {
        navAuth.innerHTML = `
            <a href="login-agent.html" class="btn btn-outline-primary btn-sm">Connexion</a>
            <a href="register.html" class="btn btn-primary btn-sm">S'inscrire</a>`
    }
}

async function chargerBadgeMessages() {
    const data = await apiRequest('/conversations/non-lus/count', 'GET', null, true)
    if (!data?.count) return
    const b = document.getElementById('badgeNavMessages')
    if (b && data.count > 0) { b.classList.remove('d-none'); b.textContent = data.count }
}
