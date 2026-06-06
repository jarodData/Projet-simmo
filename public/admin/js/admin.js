// ============================================
// CONFIG ADMIN
// ============================================
// const ADMIN_API = 'http://localhost:8000/api/admin'

const ADMIN_API = (typeof CONFIG !== 'undefined' ? CONFIG.API_URL : 'https://simmo-laravel.onrender.com/api') + '/admin'
function getAdminToken() {
    return localStorage.getItem('simmo_admin_token')
}

function getAdmin() {
    const a = localStorage.getItem('simmo_admin')
    return a ? JSON.parse(a) : null
}

function headersAdmin() {
    return {
        'Content-Type' : 'application/json',
        'Accept'       : 'application/json',
        'Authorization': `Bearer ${getAdminToken()}`,
    }
}

function deconnexionAdmin() {
    localStorage.removeItem('simmo_admin_token')
    localStorage.removeItem('simmo_admin')
    window.location.href = 'login.html'
}

function requiertAdmin() {
    if (!getAdminToken()) {
        window.location.href = 'login.html'
    }
}

async function adminRequest(endpoint, method = 'GET', body = null) {
    const options = {
        method,
        headers: headersAdmin(),
    }
    if (body) options.body = JSON.stringify(body)

    const response = await fetch(`${ADMIN_API}${endpoint}`, options)
    const data     = await response.json()

    if (response.status === 401) {
        deconnexionAdmin()
        return
    }

    if (!response.ok) throw { status: response.status, data }
    return data
}

// Navbar admin commune
function initAdminNavbar(pageActive = '') {
    requiertAdmin()
    const admin = getAdmin()

    const menu = [
        { href: 'dashboard.html',   icon: 'bi-grid',        label: 'Dashboard'    },
        { href: 'utilisateurs.html',icon: 'bi-people',      label: 'Utilisateurs' },
        { href: 'agents.html',      icon: 'bi-person-badge',label: 'Agents'       },
        { href: 'annonces.html',    icon: 'bi-house',       label: 'Annonces'     },
        { href: 'plans.html',       icon: 'bi-credit-card', label: 'Plans'        },
        { href: 'paiements.html',   icon: 'bi-cash-stack',  label: 'Paiements'    },
    ]

    document.getElementById('adminSidebar').innerHTML = `
        <div class="p-3 border-bottom">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="bg-primary rounded-2 p-1">
                    <i class="bi bi-shield-lock text-white"></i>
                </div>
                <span class="fw-bold">SIMMo Admin</span>
            </div>
            <p class="text-muted small mb-0">
                ${admin?.prenom} ${admin?.nom}
            </p>
        </div>
        <nav class="p-2 flex-grow-1">
            ${menu.map(m => `
                <a href="${m.href}"
                    class="nav-link d-flex align-items-center gap-2 px-3 py-2
                        rounded-2 mb-1 ${pageActive === m.href
                            ? 'bg-primary text-white'
                            : 'text-dark'}">
                    <i class="bi ${m.icon}"></i>
                    ${m.label}
                </a>`).join('')}
        </nav>
        <div class="p-3 border-top">
            <button class="btn btn-outline-danger btn-sm w-100"
                onclick="deconnexionAdmin()">
                <i class="bi bi-box-arrow-right me-2"></i>
                Déconnexion
            </button>
        </div>
    `
}

// Formater les dates
function formatDate(date) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    })
}

// Badge statut
function badgeStatut(statut) {
    const config = {
        'actif'      : 'bg-success',
        'en_attente' : 'bg-warning text-dark',
        'suspendu'   : 'bg-danger',
        'active'     : 'bg-success',
        'inactive'   : 'bg-secondary',
        'vendu'      : 'bg-primary',
        'succes'     : 'bg-success',
        'echec'      : 'bg-danger',
        'en_attente' : 'bg-warning text-dark',
    }
    return `<span class="badge ${config[statut] || 'bg-secondary'}">
        ${statut}
    </span>`
}