{{-- resources/views/admin/cni/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Vérification CNI')

@section('content')
<div class="container-fluid py-4">

    {{-- En-tête --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="bi bi-shield-check text-primary me-2"></i>
                Vérification CNI
            </h4>
            <p class="text-muted small mb-0">Gestion des cartes d'identité des agents</p>
        </div>
        <a href="{{ route('admin.cni.verifier') }}" class="btn btn-primary fw-bold">
            <i class="bi bi-cpu me-2"></i>Analyser une CNI
        </a>
    </div>

    {{-- Statistiques --}}
    <div class="row g-3 mb-4">
        @php
        $cartes = [
            ['label'=>'Total agents',      'val'=>$stats['total'],      'icon'=>'bi-people',          'color'=>'primary'],
            ['label'=>'En attente',        'val'=>$stats['en_attente'], 'icon'=>'bi-hourglass-split',  'color'=>'warning'],
            ['label'=>'CNI approuvées',    'val'=>$stats['approuvees'], 'icon'=>'bi-check-circle',     'color'=>'success'],
            ['label'=>'CNI rejetées',      'val'=>$stats['rejetees'],   'icon'=>'bi-x-circle',         'color'=>'danger'],
            ['label'=>'Vérif. manuelle',   'val'=>$stats['manuelles'],  'icon'=>'bi-eye',              'color'=>'info'],
        ];
        @endphp
        @foreach($cartes as $c)
        <div class="col-6 col-md">
            <a href="{{ route('admin.cni.index', ['statut' => $loop->index === 0 ? 'tous' : ['tous','en_attente','approuve','rejete','manuel'][$loop->index]]) }}"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center py-3">
                        <i class="bi {{ $c['icon'] }} text-{{ $c['color'] }} fs-3 mb-2 d-block"></i>
                        <h4 class="fw-bold mb-0">{{ $c['val'] }}</h4>
                        <p class="text-muted small mb-0">{{ $c['label'] }}</p>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['tous'=>'Tous','en_attente'=>'En attente','approuve'=>'Approuvées','rejete'=>'Rejetées','manuel'=>'Vérif. manuelle'] as $val => $label)
                <a href="{{ route('admin.cni.index', ['statut'=>$val]) }}"
                   class="btn btn-sm {{ $statut === $val ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                    @if($val === 'en_attente' && $stats['en_attente'] > 0)
                        <span class="badge bg-danger ms-1">{{ $stats['en_attente'] }}</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Table agents --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Agent</th>
                            <th>Statut compte</th>
                            <th>Statut CNI</th>
                            <th>Score IA</th>
                            <th>Analysé le</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($agents as $agent)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $agent->nom_complet }}</div>
                                <div class="text-muted small">{{ $agent->email }}</div>
                            </td>
                            <td>
                                @php
                                $couleurs = ['actif'=>'success','en_attente'=>'warning','rejete'=>'danger','suspendu'=>'secondary'];
                                @endphp
                                <span class="badge bg-{{ $couleurs[$agent->statut] ?? 'secondary' }} bg-opacity-15 text-{{ $couleurs[$agent->statut] ?? 'secondary' }}">
                                    {{ ucfirst($agent->statut) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $agent->cni_couleur }} bg-opacity-15 text-{{ $agent->cni_couleur }}">
                                    {{ $agent->cni_statut_label }}
                                </span>
                                @if($agent->cni_verif_manuelle)
                                    <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Vérification manuelle requise"></i>
                                @endif
                            </td>
                            <td>
                                @if($agent->cni_score_confiance)
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:60px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden">
                                            <div style="width:{{ $agent->score_pourcentage }}%;height:100%;background:{{ $agent->score_pourcentage >= 80 ? '#16a34a' : ($agent->score_pourcentage >= 50 ? '#d97706' : '#dc2626') }};border-radius:3px"></div>
                                        </div>
                                        <span class="small fw-bold">{{ $agent->score_pourcentage }}%</span>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $agent->cni_analyse_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.cni.show', $agent) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($agent->cni_verif_manuelle || !$agent->cni_recommandation)
                                <a href="{{ route('admin.cni.verifier') }}?agent={{ $agent->id }}"
                                   class="btn btn-sm btn-outline-warning ms-1">
                                    <i class="bi bi-cpu"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                                Aucun agent dans cette catégorie
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($agents->hasPages())
        <div class="card-footer bg-white">
            {{ $agents->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
