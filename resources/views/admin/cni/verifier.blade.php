{{-- resources/views/admin/cni/verifier.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Analyser une CNI')

@section('content')
<div class="container-lg py-4">

    <div class="mb-4">
        <a href="{{ route('admin.cni.index') }}" class="text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
        </a>
        <h4 class="fw-bold mt-1 mb-0">
            <i class="bi bi-cpu text-primary me-2"></i>
            Analyser une CNI par IA
        </h4>
    </div>

    <div class="row g-4">

        {{-- ── Colonne gauche : upload ── --}}
        <div class="col-12 col-lg-5">

            {{-- Zone de drop --}}
            <div id="dropZone"
                 class="border border-2 border-dashed rounded-4 p-5 text-center"
                 style="cursor:pointer;border-color:#cbd5e1!important;transition:all .2s;background:white"
                 onclick="document.getElementById('fileInput').click()"
                 ondragover="dragOver(event)" ondragleave="dragLeave(event)" ondrop="dropped(event)">
                <div id="dropContent">
                    <i class="bi bi-id-card" style="font-size:3.5rem;color:#94a3b8"></i>
                    <p class="fw-semibold mt-3 mb-1">Déposez la CNI ici</p>
                    <p class="text-muted small mb-3">ou cliquez pour sélectionner</p>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">JPG · PNG · WEBP · max 5 Mo</span>
                </div>
                <img id="preview" src="" style="display:none;max-height:220px;max-width:100%;border-radius:10px;object-fit:contain">
                <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp"
                       style="display:none" onchange="fichierSelectionne(this.files[0])">
            </div>

            <p id="nomFichier" class="text-muted small mt-2 text-center" style="display:none"></p>

            {{-- Sélecteur agent --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <label class="form-label fw-semibold small text-muted mb-1">
                        <i class="bi bi-person me-1"></i>Agent lié (optionnel)
                    </label>
                    <select id="agentSelect" class="form-select form-select-sm">
                        <option value="">— Analyse sans lier à un agent —</option>
                        @foreach($agents as $a)
                        <option value="{{ $a->id }}" {{ optional($agent)->id === $a->id ? 'selected' : '' }}>
                            {{ $a->nom_complet }} (ID: {{ $a->id }})
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text">Si sélectionné, le statut de l'agent sera mis à jour.</div>
                </div>
            </div>

            <button class="btn btn-primary w-100 mt-3 fw-bold py-2"
                    id="btnAnalyser" onclick="analyser()" disabled>
                <i class="bi bi-cpu me-2"></i>Analyser avec l'IA
            </button>

        </div>

        {{-- ── Colonne droite : résultat ── --}}
        <div class="col-12 col-lg-7">
            <div id="resultContainer">
                <div class="card border-0 shadow-sm p-5 text-center text-muted h-100">
                    <i class="bi bi-arrow-left-circle" style="font-size:2.5rem;opacity:.3"></i>
                    <p class="mt-3 mb-0">Le résultat de l'analyse apparaîtra ici</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Overlay analyse --}}
<div id="overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);
    z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:16px;color:white">
    <div class="spinner-border" style="width:3rem;height:3rem"></div>
    <p class="fw-semibold fs-5 mb-0">Analyse IA en cours…</p>
    <small style="opacity:.6">Extraction des données CNI</small>
</div>

@push('scripts')
<script>
const ANALYSER_URL  = '{{ route('admin.cni.analyser') }}';
const VALIDER_BASE  = '{{ url('admin/cni/valider') }}';
const DECISION_BASE = '{{ url('admin/cni/decision') }}';
const CSRF          = '{{ csrf_token() }}';

let fichierCourant = null;

// ── Drag & Drop ──
function dragOver(e) {
    e.preventDefault();
    const dz = document.getElementById('dropZone');
    dz.style.borderColor  = '#2563eb';
    dz.style.background   = '#eff6ff';
}
function dragLeave() {
    const dz = document.getElementById('dropZone');
    dz.style.borderColor  = '#cbd5e1';
    dz.style.background   = 'white';
}
function dropped(e) {
    e.preventDefault();
    dragLeave();
    fichierSelectionne(e.dataTransfer.files[0]);
}

function fichierSelectionne(f) {
    if (!f) return;
    if (!['image/jpeg','image/png','image/webp'].includes(f.type)) {
        alert('Format non supporté (JPG, PNG, WEBP uniquement)'); return;
    }
    if (f.size > 5 * 1024 * 1024) { alert('Fichier trop lourd (max 5 Mo)'); return; }

    fichierCourant = f;
    const pr = document.getElementById('preview');
    pr.src = URL.createObjectURL(f);
    pr.style.display = 'block';
    document.getElementById('dropContent').style.display = 'none';
    document.getElementById('dropZone').style.borderColor = '#16a34a';
    document.getElementById('nomFichier').textContent = `📎 ${f.name} (${(f.size/1024).toFixed(0)} Ko)`;
    document.getElementById('nomFichier').style.display = 'block';
    document.getElementById('btnAnalyser').disabled = false;
    document.getElementById('resultContainer').innerHTML = `
        <div class="card border-0 shadow-sm p-4 text-center text-muted">
            <i class="bi bi-hourglass" style="font-size:2rem;opacity:.3"></i>
            <p class="mt-2 mb-0 small">Cliquez sur "Analyser" pour lancer l'IA</p>
        </div>`;
}

// ── Analyser ──
async function analyser() {
    if (!fichierCourant) return;

    const overlay = document.getElementById('overlay');
    overlay.style.display = 'flex';
    document.getElementById('btnAnalyser').disabled = true;

    try {
        const agentId = document.getElementById('agentSelect').value;
        const fd      = new FormData();
        fd.append('fichier', fichierCourant);
        fd.append('_token', CSRF);

        const url  = agentId ? `${VALIDER_BASE}/${agentId}` : ANALYSER_URL;
        const resp = await fetch(url, { method: 'POST', body: fd });
        const data = await resp.json();

        if (!resp.ok) throw new Error(data.erreur || `Erreur ${resp.status}`);

        const analyse = data.analyse || data;
        afficherResultat(analyse, agentId);

    } catch (e) {
        document.getElementById('resultContainer').innerHTML = `
            <div class="alert alert-danger d-flex gap-2 align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div><strong>Erreur</strong><br><small>${e.message}</small></div>
            </div>`;
    } finally {
        overlay.style.display = 'none';
        document.getElementById('btnAnalyser').disabled = false;
    }
}

// ── Afficher résultat ──
function afficherResultat(a, agentId) {
    const reco    = a.recommandation ?? 'INCONNU';
    const couleur = a.statut_couleur ?? 'secondary';
    const score   = Math.round((a.score_confiance ?? 0) * 100);
    const d       = a.donnees_extraites ?? {};
    const v       = a.verification ?? {};
    const anom    = a.anomalies ?? [];

    const titres = {
        APPROUVER             : ['check-circle-fill', 'CNI VALIDE — APPROUVÉE'],
        REJETER               : ['x-circle-fill',     'CNI REJETÉE'],
        VERIFIER_MANUELLEMENT : ['exclamation-triangle-fill', 'VÉRIFICATION MANUELLE REQUISE'],
    };
    const [icone, titre] = titres[reco] ?? ['question-circle', reco];
    const cg = score >= 80 ? '#16a34a' : score >= 50 ? '#d97706' : '#dc2626';

    const verifications = {
        document_authentique           : 'Document authentique',
        photo_presente                 : 'Photo présente',
        texte_lisible                  : 'Texte lisible',
        republique_cameroun_mentionnee : 'République du Cameroun',
        numero_format_valide           : 'Format numéro valide',
        non_expire                     : 'Non expirée',
        coherence_donnees              : 'Données cohérentes',
    };

    const champ = (label, val, mono=false) =>
        val != null && val !== '' ? `
        <div class="d-flex align-items-center py-2 border-bottom" style="font-size:13px">
            <span style="width:170px;color:#64748b;font-weight:500">${label}</span>
            <span class="fw-bold${mono?' font-monospace':''}" style="color:#1e293b">${val}</span>
        </div>` : '';

    document.getElementById('resultContainer').innerHTML = `
    <div class="card border-2 border-${couleur} border-opacity-75 shadow-sm overflow-hidden">

        <div class="p-3 d-flex align-items-center gap-3 fw-bold"
             style="background:var(--bs-${couleur}-bg,#f8fafc);font-size:15px">
            <i class="bi bi-${icone} text-${couleur} fs-4"></i>
            <span class="text-${couleur}">${titre}</span>
        </div>

        <div class="p-4">
            {{-- Score --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-semibold text-muted">Score de confiance IA</span>
                    <span class="fw-bold" style="color:${cg}">${score}%</span>
                </div>
                <div style="height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden">
                    <div style="width:${score}%;height:100%;background:${cg};border-radius:4px;transition:width .8s"></div>
                </div>
                ${a.motif_recommandation ? `<p class="text-muted small mt-1 mb-0">${a.motif_recommandation}</p>` : ''}
            </div>

            {{-- Données --}}
            <h6 class="fw-bold mb-2"><i class="bi bi-person-vcard text-primary me-1"></i>Données extraites</h6>
            <div class="mb-4">
                ${champ('Nom',           d.nom)}
                ${champ('Prénom(s)',     d.prenoms)}
                ${champ('N° CNI',        d.numero_cni, true)}
                ${champ('Date naiss.',   d.date_naissance)}
                ${champ('Lieu naiss.',   d.lieu_naissance)}
                ${champ('Sexe',          d.sexe)}
                ${champ('Âge',           d.age ? d.age+' ans' : null)}
                ${champ('Émission',      d.date_emission)}
                ${champ('Expiration',    d.date_expiration)}
                ${d.jours_avant_expiration != null
                    ? champ('Expire dans', d.jours_avant_expiration < 0
                        ? `<span class="text-danger">${Math.abs(d.jours_avant_expiration)} jours (EXPIRÉE)</span>`
                        : `${d.jours_avant_expiration} jours`)
                    : ''}
            </div>

            {{-- Vérifications --}}
            <h6 class="fw-bold mb-2"><i class="bi bi-list-check text-success me-1"></i>Points de contrôle</h6>
            <div class="row g-0 mb-4">
                ${Object.entries(verifications).map(([k, label]) =>
                    v[k] !== undefined ? `
                    <div class="col-6 d-flex align-items-center gap-1 py-1" style="font-size:12px">
                        <i class="bi ${v[k] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'}"></i>
                        ${label}
                    </div>` : ''
                ).join('')}
            </div>

            {{-- Anomalies --}}
            ${anom.length ? `
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle text-danger me-1"></i>Anomalies</h6>
            <div class="mb-3">
                ${anom.map(x => `
                <span class="badge bg-danger bg-opacity-10 text-danger me-1 mb-1 p-2">
                    <i class="bi bi-dot"></i>${x}
                </span>`).join('')}
            </div>` : ''}

            <p class="text-muted" style="font-size:11px">
                Analysé le ${a.analyse_at ? new Date(a.analyse_at).toLocaleString('fr-FR') : '—'} &nbsp;•&nbsp;
                Score lisibilité : ${Math.round((a.score_lisibilite??0)*100)}%
            </p>
        </div>

        {{-- Actions --}}
        <div class="px-4 pb-4 d-flex gap-2 flex-wrap">
            ${agentId ? `
            <button class="btn btn-success btn-sm fw-bold"
                    onclick="decisionAdmin('${agentId}','approuver')">
                <i class="bi bi-check2-circle me-1"></i>Approuver
            </button>
            <button class="btn btn-danger btn-sm fw-bold"
                    onclick="decisionAdmin('${agentId}','rejeter')">
                <i class="bi bi-x-circle me-1"></i>Rejeter
            </button>` : `
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Sélectionnez un agent pour enregistrer la décision.
            </p>`}
            <button class="btn btn-outline-secondary btn-sm ms-auto"
                    onclick="reinitialiser()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Nouvelle analyse
            </button>
        </div>
    </div>`;
}

// ── Décision manuelle ──
async function decisionAdmin(agentId, action) {
    const motif = prompt(`Motif (${action}) :`, 'Vérification manuelle admin');
    if (motif === null) return;

    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('action', action);
    fd.append('motif', motif);

    try {
        const r    = await fetch(`${DECISION_BASE}/${agentId}`, { method:'POST', body:fd });
        const data = await r.json();
        alert(`✅ Agent mis à jour → ${data.nouveau_statut}`);
        window.location.href = `{{ url('admin/cni') }}/${agentId}`;
    } catch (e) {
        alert('Erreur : ' + e.message);
    }
}

function reinitialiser() {
    fichierCourant = null;
    const dz = document.getElementById('dropZone');
    dz.style.borderColor = '#cbd5e1';
    dz.style.background  = 'white';
    document.getElementById('preview').style.display = 'none';
    document.getElementById('dropContent').style.display = 'block';
    document.getElementById('nomFichier').style.display = 'none';
    document.getElementById('btnAnalyser').disabled = true;
    document.getElementById('fileInput').value = '';
    document.getElementById('resultContainer').innerHTML = `
        <div class="card border-0 shadow-sm p-5 text-center text-muted">
            <i class="bi bi-arrow-left-circle" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-3 mb-0">Le résultat de l'analyse apparaîtra ici</p>
        </div>`;
}
</script>
@endpush
@endsection
