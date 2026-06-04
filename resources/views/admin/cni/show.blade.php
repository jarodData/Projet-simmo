{{-- resources/views/admin/cni/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Détail CNI — '.$agent->nom_complet)

@section('content')
<div class="container-lg py-4">

    <div class="mb-4">
        <a href="{{ route('admin.cni.index') }}" class="text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Retour à la liste
        </a>
        <h4 class="fw-bold mt-1 mb-0">Dossier CNI — {{ $agent->nom_complet }}</h4>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">

        {{-- Colonne gauche --}}
        <div class="col-12 col-lg-5">

            {{-- Image CNI --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center p-3">
                    @if($agent->cni_chemin)
                        <img src="{{ route('admin.cni.image', $agent) }}"
                             alt="CNI {{ $agent->nom_complet }}"
                             class="img-fluid rounded"
                             style="max-height:280px;object-fit:contain">
                    @else
                        <div class="py-4 text-muted">
                            <i class="bi bi-image" style="font-size:3rem;opacity:.3"></i>
                            <p class="mt-2 mb-0 small">Aucune image CNI soumise</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white text-center">
                    <a href="{{ route('admin.cni.verifier') }}?agent={{ $agent->id }}"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-cpu me-1"></i>
                        {{ $agent->cni_chemin ? 'Ré-analyser' : 'Analyser une CNI' }}
                    </a>
                </div>
            </div>

            {{-- Infos agent --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-person-badge text-primary me-2"></i>Informations agent
                </div>
                <div class="card-body">
                    @foreach([
                        'Nom complet'  => $agent->nom_complet,
                        'Email'        => $agent->email,
                        'Téléphone'    => $agent->telephone ?? '—',
                        'Statut'       => ucfirst($agent->statut),
                        'Inscrit le'   => $agent->created_at->format('d/m/Y'),
                    ] as $label => $val)
                    <div class="d-flex py-2 border-bottom" style="font-size:13px">
                        <span style="width:120px;color:#64748b">{{ $label }}</span>
                        <span class="fw-semibold">{{ $val }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div class="col-12 col-lg-7">

            {{-- Résultat IA --}}
            @if($agent->cni_recommandation)
            <div class="card border-{{ $agent->cni_couleur }} border-2 border-opacity-50 shadow-sm mb-4">
                <div class="card-header bg-{{ $agent->cni_couleur }} bg-opacity-10 fw-bold text-{{ $agent->cni_couleur }}">
                    @php
                    $icones = ['APPROUVER'=>'check-circle-fill','REJETER'=>'x-circle-fill','VERIFIER_MANUELLEMENT'=>'exclamation-triangle-fill'];
                    @endphp
                    <i class="bi bi-{{ $icones[$agent->cni_recommandation] ?? 'question-circle' }} me-2"></i>
                    {{ $agent->cni_statut_label }}
                </div>
                <div class="card-body">

                    {{-- Score --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted fw-semibold">Score de confiance IA</span>
                            <span class="fw-bold">{{ $agent->score_pourcentage }}%</span>
                        </div>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar bg-{{ $agent->cni_couleur }}"
                                 style="width:{{ $agent->score_pourcentage }}%"></div>
                        </div>
                    </div>

                    {{-- Données extraites --}}
                    @if($agent->cni_donnees)
                    <h6 class="fw-bold mb-2 mt-3">Données extraites</h6>
                    @php $d = $agent->cni_donnees; @endphp
                    <div class="row g-2" style="font-size:13px">
                        @foreach([
                            'Nom'           => $d['nom'] ?? null,
                            'Prénom(s)'     => $d['prenoms'] ?? null,
                            'N° CNI'        => $d['numero_cni'] ?? null,
                            'Date naiss.'   => $d['date_naissance'] ?? null,
                            'Lieu naiss.'   => $d['lieu_naissance'] ?? null,
                            'Sexe'          => $d['sexe'] ?? null,
                            'Âge'           => isset($d['age']) ? $d['age'].' ans' : null,
                            'Expiration'    => $d['date_expiration'] ?? null,
                        ] as $label => $val)
                        @if($val)
                        <div class="col-6">
                            <span class="text-muted d-block">{{ $label }}</span>
                            <span class="fw-semibold">{{ $val }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @endif

                    {{-- Anomalies --}}
                    @if($agent->cni_anomalies_list)
                    <div class="mt-3">
                        <h6 class="fw-bold mb-2 text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>Anomalies
                        </h6>
                        @foreach($agent->cni_anomalies_list as $anom)
                        <span class="badge bg-danger bg-opacity-10 text-danger me-1 mb-1 p-2">{{ $anom }}</span>
                        @endforeach
                    </div>
                    @endif

                    <p class="text-muted mt-3 mb-0" style="font-size:11px">
                        Analysé le {{ $agent->cni_analyse_at?->format('d/m/Y à H:i') }}
                    </p>
                </div>
            </div>
            @endif

            {{-- Décision manuelle --}}
            @if(!$agent->cni_decision_admin)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-person-check me-2 text-warning"></i>Décision manuelle
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.cni.decision', $agent) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Motif</label>
                            <input type="text" name="motif" class="form-control form-control-sm"
                                   placeholder="Ex: Document vérifié physiquement en agence"
                                   value="Vérification manuelle admin">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="approuver"
                                    class="btn btn-success btn-sm fw-bold">
                                <i class="bi bi-check2-circle me-1"></i>Approuver
                            </button>
                            <button type="submit" name="action" value="rejeter"
                                    class="btn btn-danger btn-sm fw-bold">
                                <i class="bi bi-x-circle me-1"></i>Rejeter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <i class="bi bi-person-check-fill me-2"></i>
                Décision admin : <strong>{{ ucfirst($agent->cni_decision_admin) }}</strong>
                @if($agent->cni_motif_admin)
                    — {{ $agent->cni_motif_admin }}
                @endif
                @if($agent->cni_decision_at)
                    <br><small class="text-muted">Le {{ $agent->cni_decision_at->format('d/m/Y à H:i') }}</small>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
