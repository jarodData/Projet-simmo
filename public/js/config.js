const CONFIG = {
    API_URL     : window.location.origin + "/api",
    STORAGE_URL : window.location.origin + "/storage",
}

// const CONFIG = {
//     API_URL     : window.location.origin + '/api',
//     STORAGE_URL : window.location.origin + '/storage',
// }

const IA_URL = 'http://127.0.0.1:8001/api/ia'
const IA_KEY = 'simmo_ia_secret_key'




// Récupérer le token
function getToken() {
    return localStorage.getItem('simmo_token')
}

function getUser() {
    var u = localStorage.getItem('simmo_user')
    return u ? JSON.parse(u) : null
}

function getRole() {
    return localStorage.getItem('simmo_role')
}

function estConnecte() {
    return !!getToken()
}

function estAgent() {
    return getRole() === 'agent'
}

function headersAuth() {
    return {
        'Content-Type' : 'application/json',
        'Accept'       : 'application/json',
        'Authorization': 'Bearer ' + getToken(),
    }
}

function headersPublic() {
    return {
        'Content-Type': 'application/json',
        'Accept'      : 'application/json',
    }
}

function deconnexion() {
    localStorage.removeItem('simmo_token')
    localStorage.removeItem('simmo_user')
    localStorage.removeItem('simmo_role')
    window.location.href = '/index.html'
}

function imageUrl(chemin) {

    if (
        !chemin ||
        chemin === 'null' ||
        chemin === 'undefined' ||
        chemin === 'None' ||
        chemin === ''
    ) {
        return 'https://placehold.co/400x250/e2e8f0/64748b?text=SIMM'
    }

    // URL déjà complète
    if (
        chemin.startsWith('http://') ||
        chemin.startsWith('https://')
    ) {
        return chemin
    }

    // Nettoyer slash
    chemin = chemin.replace(/^\/+/, '')

    return CONFIG.STORAGE_URL + '/' + chemin
}

function formatPrix(prix) {
    return Number(prix).toLocaleString('fr-FR') + ' F CFA'
}

function showAlert(message, type, containerId) {
    var id        = containerId || 'alertContainer'
    var container = document.getElementById(id)
    if (!container) return
    container.innerHTML =
        '<div class="alert alert-' + type +
        ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close"' +
                ' data-bs-dismiss="alert"></button>' +
        '</div>'
    setTimeout(function() {
        var alert = container.querySelector('.alert')
        if (alert) alert.remove()
    }, 4000)
}

function showSpinner(btnId, texte) {
    var btn = document.getElementById(btnId)
    if (btn) {
        btn.disabled  = true
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>' +
            texte
    }
}

function hideSpinner(btnId, texteOriginal) {
    var btn = document.getElementById(btnId)
    if (btn) {
        btn.disabled  = false
        btn.innerHTML = texteOriginal
    }
}

async function apiRequest(endpoint, method, body, avecToken) {
    method    = method    || 'GET'
    avecToken = avecToken || false

    var options = {
        method : method,
        headers: avecToken ? headersAuth() : headersPublic(),
    }

    if (body && method !== 'GET') {
        options.body = JSON.stringify(body)
    }

    var response = await fetch(CONFIG.API_URL + endpoint, options)
    var data     = await response.json()

    if (!response.ok) {
        if (response.status === 401) {
            deconnexion()
        }
        throw { status: response.status, data: data }
    }

    return data
}

async function apiFormData(endpoint, formData) {
    var headers = {
        'Accept': 'application/json',
    }
    if (getToken()) {
        headers['Authorization'] = 'Bearer ' + getToken()
    }

    var response = await fetch(CONFIG.API_URL + endpoint, {
        method : 'POST',
        headers: headers,
        body   : formData,
    })

    var data = await response.json()
    if (!response.ok) throw { status: response.status, data: data }
    return data
}

async function appelIA(endpoint, body) {
    try {
        var response = await fetch(IA_URL + endpoint, {
            method  : 'POST',
            headers : {
                'Content-Type' : 'application/json',
                'x-api-key'    : IA_KEY,
            },
            body: JSON.stringify(body),
        })
        if (!response.ok) throw new Error('Erreur IA')
        return await response.json()
    } catch (e) {
        console.warn('API IA indisponible:', e.message)
        return null
    }
}

function buildCriteresIA(opts) {
    opts = opts || {}
    return {
        ville            : opts.ville            || null,
        type_bien        : opts.type_bien         || null,
        type_transaction : opts.type_transaction  || null,
        budget_min       : opts.budget_min
            ? parseFloat(opts.budget_min) : null,
        budget_max       : opts.budget_max
            ? parseFloat(opts.budget_max) : null,
        nb_chambres      : opts.nb_chambres
            ? parseInt(opts.nb_chambres)  : null,
        surface_min      : opts.surface_min
            ? parseFloat(opts.surface_min): null,
        meuble           : opts.meuble || null,
        limite           : opts.limite || 12,
    }
}


// js/config.js — en bas du fichier
function initNavbar() {
    const navAuth      = document.getElementById('navAuth')
    const navDashboard = document.getElementById('navDashboard')

    if (estConnecte()) {
        const user = getUser()
        if (estAgent()) navDashboard.style.display = 'block'

        navAuth.innerHTML =
            '<div class="dropdown">' +
                '<button class="btn btn-light dropdown-toggle' +
                    ' d-flex align-items-center gap-2"' +
                    ' type="button" data-bs-toggle="dropdown">' +
                    '<div class="bg-primary text-white rounded-circle' +
                        ' d-flex align-items-center justify-content-center' +
                        ' fw-bold"' +
                        ' style="width:30px;height:30px;font-size:12px">' +
                        (user ? user.prenom[0].toUpperCase() : 'U') +
                    '</div>' +
                    '<span class="fw-semibold small d-none d-md-inline">' +
                        (user ? user.prenom : '') +
                    '</span>' +
                '</button>' +

                '<ul class="dropdown-menu dropdown-menu-end' +
                    ' shadow border-0">' +
                        
                    // Profil
                    '<li>' +
                        '<a class="dropdown-item" href="profil.html">' +
                            '<i class="bi bi-person me-2 text-primary"></i>' +
                            'Mon profil' +
                        '</a>' +
                    '</li>' +
                    

                    //  Favoris — UNIQUEMENT pour les utilisateurs
                    (!estAgent() ?
                        '<li>' +
                            '<a class="dropdown-item"' +
                                ' href="mes-favoris.html">' +
                                '<i class="bi bi-heart-fill' +
                                    ' text-danger me-2"></i>' +
                                'Mes favoris' +
                            '</a>' +
                        '</li>'
                    : '') +

                    //  Dashboard — UNIQUEMENT pour les agents
                    (estAgent() ?
                        '<li>' +
                            '<a class="dropdown-item"' +
                                ' href="dashboard.html">' +
                                '<i class="bi bi-grid' +
                                    ' me-2 text-primary"></i>' +
                                'Dashboard' +
                            '</a>' +
                        '</li>' +
                        '<li>' +
                            '<a class="dropdown-item"' +
                                ' href="chat.html">' +
                                '<i class="bi bi-chat-dots' +
                                    ' me-2 text-primary"></i>' +
                                'Mes messages' +
                            '</a>' +
                        '</li>'
                    : '') +
                    '<li>' +
                    '<a class="dropdown-item" href="recherche-description.html">' +
                        '<i class="bi bi-search me-2 text-primary"></i>' +
                        'Recherche description' +
                    '</a>'
                    '</li>'

                    //  Divider
                    '<li><hr class="dropdown-divider"></li>' +

                    //  Deconnexion
                    '<li>' +
                        '<button class="dropdown-item text-danger"' +
                            ' onclick="deconnexion()">' +
                            '<i class="bi bi-box-arrow-right me-2"></i>' +
                            'Deconnexion' +
                        '</button>' +
                    '</li>' +

                '</ul>' +
            '</div>'

    } else {
        navAuth.innerHTML =
            '<a href="login.html"' +
                ' class="btn btn-outline-primary btn-sm">' +
                'Connexion' +
            '</a>' +
            '<a href="register.html"' +
                ' class="btn btn-primary btn-sm">' +
                "S'inscrire" +
            '</a>'
    }

}