// ============================================================
// À mettre dans index.html — remplace carteAnnonceIA()
// Photos corrigées + bouton favori + envoi user_id à l'IA
// ============================================================

// IDs favoris chargés au démarrage
let idsFavoris = new Set()

async function chargerIdsFavoris() {
    const token = localStorage.getItem('token')
    if (!token) return
    try {
        const res  = await fetch(CONFIG.API_URL + '/favoris/ids', {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        const data = await res.json()
        idsFavoris = new Set(data.ids || [])
    } catch (e) { /* non connecté = pas grave */ }
}

async function toggleFavori(event, annonceId) {
    event.preventDefault()
    event.stopPropagation()

    const token = localStorage.getItem('token')
    if (!token) {
        window.location.href = 'login.html'
        return
    }

    try {
        const res  = await fetch(CONFIG.API_URL + '/favoris/toggle', {
            method : 'POST',
            headers: {
                'Content-Type' : 'application/json',
                'Authorization': 'Bearer ' + token,
            },
            body: JSON.stringify({ annonce_id: annonceId })
        })
        const data = await res.json()

        // Mettre à jour le cœur sans recharger
        const btn = event.currentTarget
        if (data.favori) {
            idsFavoris.add(annonceId)
            btn.innerHTML  = '<i class="bi bi-heart-fill text-danger"></i>'
        } else {
            idsFavoris.delete(annonceId)
            btn.innerHTML  = '<i class="bi bi-heart text-white"></i>'
        }
    } catch (e) { console.error('Erreur favori:', e) }
}

// ── Chargement annonces avec user_id pour personnalisation ──
async function chargerAnnonces() {
    var container = document.getElementById('annoncesRecentes')
    container.innerHTML =
        '<div class="col-12 text-center py-4">' +
            '<div class="spinner-border text-primary"></div>' +
        '</div>'

    await chargerIdsFavoris()

    const user   = JSON.parse(localStorage.getItem('user') || 'null')
    const userId = user?.id || null

    try {
        const res    = await fetch(CONFIG.IA_URL + '/ia/recommander', {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-KEY'   : CONFIG.IA_KEY,
            },
            body: JSON.stringify({ limite: 6, user_id: userId })
        })
        const iaData = await res.json()

        if (iaData?.recommandations?.length > 0) {
            container.innerHTML = iaData.recommandations
                .map(r => carteAnnonceIA(r))
                .join('')
            return
        }

        // Fallback Laravel
        const resLaravel = await fetch(CONFIG.API_URL + '/annonces?limit=6',
            { headers: { 'Accept': 'application/json' } })
        const data       = await resLaravel.json()
        const annonces   = data.data || []

        if (annonces.length === 0) {
            container.innerHTML =
                '<div class="col-12 text-center py-5 text-muted">' +
                    '<p>Aucune annonce disponible.</p></div>'
            return
        }
        container.innerHTML = annonces
            .map(a => carteAnnonceClassique(a))
            .join('')

    } catch (e) {
        console.error('Erreur:', e)
        container.innerHTML =
            '<div class="col-12 text-center text-muted py-5">' +
                '<p>Erreur de chargement.</p></div>'
    }
}

// ── Carte annonce IA avec photo et favori ───────────────────
function carteAnnonceIA(r) {
    var idAnnonce = r.id_annonce || r.id

    // ✅ Photo : chemin depuis la BD → préfixe STORAGE_URL
    var urlPhoto
    if (r.photo && r.photo !== 'null' && r.photo !== '') {
        // Éviter double slash
        var chemin = r.photo.startsWith('/') ? r.photo : '/' + r.photo
        urlPhoto   = CONFIG.STORAGE_URL.replace(/\/$/, '') + chemin
    } else {
        urlPhoto = genererPlaceholder(r.titre)
    }

    var estFavori = idsFavoris.has(idAnnonce)
    var quartier  = r.quartier ? r.quartier + ', ' : ''
    var badge     = r.type_transaction === 'location'
        ? '<span class="badge bg-primary badge-transaction">Location</span>'
        : '<span class="badge bg-success badge-transaction">Vente</span>'

    return `
        <div class="col-12 col-md-6 col-lg-4">
            <a href="annonce-detail.html?id=${idAnnonce}"
               class="carte-annonce card border-0 shadow-sm text-decoration-none">
                <div class="card-img-wrapper">
                    <img
                        src="${urlPhoto}"
                        class="card-img-top"
                        alt="${r.titre}"
                        onerror="this.onerror=null;this.src=genererPlaceholder('${(r.titre||'').replace(/'/g,'')}');"
                    >
                    ${badge}

                    <!-- Bouton favori -->
                    <button
                        class="btn btn-sm position-absolute"
                        style="top:8px;right:8px;background:rgba(0,0,0,0.35);
                               border:none;border-radius:50%;width:34px;height:34px;
                               display:flex;align-items:center;justify-content:center;
                               padding:0;z-index:3"
                        onclick="toggleFavori(event, ${idAnnonce})"
                        title="${estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris'}"
                    >
                        <i class="bi ${estFavori ? 'bi-heart-fill text-danger' : 'bi-heart text-white'}"></i>
                    </button>

                    <span class="position-absolute bottom-0 end-0
                        m-2 badge bg-dark bg-opacity-75 small">
                        IA ${Math.round((r.score_final || 0.5) * 100)}%
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-primary small fw-bold text-uppercase mb-1">
                        ${r.categorie || ''}
                    </p>
                    <h5 class="card-title fw-bold text-truncate">${r.titre}</h5>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-geo-alt text-primary"></i>
                        ${quartier}${r.ville || ''}
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="prix-annonce">${formatPrix(r.prix)}</span>
                        ${r.prix_estime
                            ? `<span class="badge bg-success bg-opacity-10 text-success small">
                                ~${formatPrix(r.prix_estime)}</span>`
                            : ''}
                    </div>
                    ${r.raison
                        ? `<p class="text-muted small fst-italic mt-1 mb-0 text-truncate">
                            ${r.raison}</p>`
                        : ''}
                </div>
            </a>
        </div>`
}

// ── Placeholder local sans réseau ───────────────────────────
function genererPlaceholder(texte) {
    var canvas    = document.createElement('canvas')
    canvas.width  = 400
    canvas.height = 250
    var ctx       = canvas.getContext('2d')
    ctx.fillStyle = '#e2e8f0'
    ctx.fillRect(0, 0, 400, 250)
    ctx.fillStyle    = '#94a3b8'
    ctx.font         = 'bold 40px sans-serif'
    ctx.textAlign    = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText('🏠', 200, 100)
    ctx.fillStyle = '#64748b'
    ctx.font      = 'bold 16px sans-serif'
    ctx.fillText((texte || 'SIMMo').substring(0, 22), 200, 165)
    ctx.fillStyle = '#94a3b8'
    ctx.font      = '13px sans-serif'
    ctx.fillText('Photo non disponible', 200, 190)
    return canvas.toDataURL('image/png')
}
