<?php

namespace App\Console\Commands;

use App\Jobs\DispatchCampaign;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchDueCampaigns extends Command
{
    protected $signature = 'whatstrigger:dispatch-due
                            {--dry-run : Lista campanhas prontas sem disparar}';

    protected $description = 'Despacha campanhas agendadas cujo horário já chegou.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $dispatched = 0;

        Campaign::due()
            ->with('user.subscription')
            ->chunk(50, function ($campaigns) use ($dryRun, &$dispatched) {
                foreach ($campaigns as $campaign) {
                    if ($dryRun) {
                        $this->line("  [{$campaign->id}] {$campaign->name} — {$campaign->scheduled_at}");
                        $dispatched++;

                        continue;
                    }

                    DB::transaction(function () use ($campaign, &$dispatched) {
                        // Re-verifica com lock — evita duplo despacho se o scheduler
                        // sobrepuser execuções (ex.: job travado + nova instância do scheduler)
                        $locked = Campaign::where('id', $campaign->id)
                            ->where('status', Campaign::STATUS_SCHEDULED)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked) {
                            return; // outra instância já processou
                        }

                        // Marca como 'sending' antes de enfileirar o job para garantir
                        // que uma segunda execução do comando não despache novamente
                        $locked->update(['status' => Campaign::STATUS_SENDING]);

                        DispatchCampaign::dispatch($locked);
                        $dispatched++;

                        $this->info("  [{$locked->id}] {$locked->name} despachada.");
                    });
                }
            });

        $verb = $dryRun ? 'encontrada(s)' : 'despachada(s)';
        $this->info("{$dispatched} campanha(s) {$verb}.");

        return self::SUCCESS;
    }
}
