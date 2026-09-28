<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Diploma;
use App\Models\VerificationRequest;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('authdip:repair-requests {--apply : Appliquer les corrections}', function () {
    $apply = (bool) $this->option('apply');
    $checked = $fixed = $reopened = 0;

    VerificationRequest::where('status', 'validated')->orderBy('id')->chunkById(100, function ($requests) use (&$checked, &$fixed, &$reopened, $apply) {
        foreach ($requests as $request) {
            $checked++;
            $diploma = Diploma::where('number', $request->diploma_number)->first();

            if ($diploma) {
                if ((int) $request->diploma_id !== (int) $diploma->id) {
                    $fixed++;
                    $this->line("<info>{$request->reference}</info> : rattachement au diplôme {$diploma->number}");
                    if ($apply) $request->update(['diploma_id' => $diploma->id]);
                }
                continue;
            }

            $reopened++;
            $this->warn("{$request->reference} : numéro {$request->diploma_number} introuvable, retour en attente");
            if ($apply) {
                $request->update([
                    'status' => 'pending',
                    'diploma_id' => null,
                    'decision_note' => 'Validation annulée automatiquement : le numéro du diplôme ne correspond à aucun enregistrement de la base IAI. Contrôle requis.',
                    'processed_by' => null,
                    'processed_at' => null,
                ]);
            }
        }
    });

    $this->newLine();
    $this->info(($apply ? 'Réparation appliquée' : 'Aperçu terminé').' : '.$checked.' demande(s) contrôlée(s), '.$fixed.' rattachement(s) corrigé(s), '.$reopened.' demande(s) remise(s) en attente.');
    if (! $apply && ($fixed || $reopened)) $this->comment('Relancez avec --apply pour appliquer ces corrections.');
})->purpose('Répare la cohérence des demandes déjà validées avec la base des diplômes');
