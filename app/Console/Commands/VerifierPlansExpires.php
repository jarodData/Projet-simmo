<?php

namespace App\Console\Commands;

use App\Models\AgentImmobilier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerifierPlansExpires extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    
    protected $signature = 'simmo:verifier-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie et désactive les plans expirés';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $agentsExpires = AgentImmobilier::where('statut', 'actif')
            ->whereNotNull('date_expiration_plan')
            ->where('date_expiration_plan', '<', Carbon::now())
            ->get();

        foreach ($agentsExpires as $agent) {
            $agent->update(['statut' => 'suspendu']);
            $this->info("Agent {$agent->email} suspendu - plan expiré.");
        }

        $this->info("Vérification terminée. {$agentsExpires->count()} agent(s) suspendu(s).");
    }
    }

