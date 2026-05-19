<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchCampaign;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookLog;
use App\Models\WhatsAppMessage;
use App\Services\EvolutionApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebController extends Controller
{
    public function __construct(
        private readonly EvolutionApiService $evolutionApi
    ) {}

    // =========================================================================
    // AUTENTICAÇÃO
    // =========================================================================

    public function loginForm(): View
    {
        return view('whatstrigger.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciais inválidas. Verifique seu e-mail e senha.']);
        }

        $request->session()->regenerate();

        return redirect()->route('wt.dashboard')
            ->with('success', 'Bem-vindo de volta, '.Auth::user()->name.'!');
    }

    public function registerForm(): View
    {
        return view('whatstrigger.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $now = now();
        Subscription::create([
            'user_id' => $user->id,
            'plan' => Subscription::PLAN_FREE,
            'messages_limit' => Subscription::limitForPlan(Subscription::PLAN_FREE),
            'messages_sent' => 0,
            'period_start' => $now->toDateString(),
            'period_end' => $now->copy()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('wt.dashboard')
            ->with('success', 'Conta criada com sucesso! Bem-vindo ao WhatsTrigger.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('wt.login');
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    public function dashboard(): View
    {
        $userId = auth()->id();
        $subscription = Subscription::where('user_id', $userId)->latest()->first();

        $sentThisMonth = WhatsAppMessage::whereHas('campaign', fn ($q) => $q->where('user_id', $userId))
            ->where('status', 'sent')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $activeCampaigns = Campaign::where('user_id', $userId)
            ->whereIn('status', [Campaign::STATUS_SENDING, Campaign::STATUS_SCHEDULED])
            ->count();

        $recentCampaigns = Campaign::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('whatstrigger.dashboard', compact(
            'subscription',
            'sentThisMonth',
            'activeCampaigns',
            'recentCampaigns'
        ));
    }

    // =========================================================================
    // CONTATOS
    // =========================================================================

    public function contactsIndex(Request $request): View
    {
        $query = Contact::where('user_id', auth()->id())
            ->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(20)->withQueryString();

        return view('whatstrigger.contacts.index', compact('contacts', 'search'));
    }

    public function contactsCreate(): View
    {
        return view('whatstrigger.contacts.create');
    }

    public function contactsStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'tags' => 'nullable|string',
            'opted_in' => 'nullable|boolean',
        ]);

        // Converte string de tags separadas por vírgula em array
        $tags = [];
        if (! empty($data['tags'])) {
            $tags = array_values(array_filter(
                array_map('trim', explode(',', $data['tags']))
            ));
        }

        Contact::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'phone' => $data['phone'],
            'tags' => $tags,
            'opted_in' => $request->boolean('opted_in'),
        ]);

        return redirect()->route('wt.contacts.index')
            ->with('success', 'Contato criado com sucesso.');
    }

    public function contactsDestroy(Contact $contact): RedirectResponse
    {
        abort_if($contact->user_id !== auth()->id(), 403);

        $contact->delete();

        return redirect()->route('wt.contacts.index')
            ->with('success', 'Contato removido.');
    }

    // =========================================================================
    // CAMPANHAS
    // =========================================================================

    public function campaignsIndex(Request $request): View
    {
        $status = $request->get('status');
        $userId = auth()->id();

        $query = Campaign::where('user_id', $userId)->orderByDesc('created_at');

        if ($status && in_array($status, [
            Campaign::STATUS_DRAFT,
            Campaign::STATUS_SCHEDULED,
            Campaign::STATUS_SENDING,
            Campaign::STATUS_COMPLETED,
            Campaign::STATUS_CANCELLED,
        ])) {
            $query->where('status', $status);
        }

        $campaigns = $query->paginate(20)->withQueryString();

        return view('whatstrigger.campaigns.index', compact('campaigns', 'status'));
    }

    public function campaignsCreate(): View
    {
        // Tags únicas do usuário para o autocomplete
        $tags = Contact::where('user_id', auth()->id())
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return view('whatstrigger.campaigns.create', compact('tags'));
    }

    public function campaignsStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:4096',
            'target_tags' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $targetTags = [];
        if (! empty($data['target_tags'])) {
            $targetTags = array_values(array_filter(
                array_map('trim', explode(',', $data['target_tags']))
            ));
        }

        $campaign = Campaign::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'message' => $data['message'],
            'target_tags' => $targetTags,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => Campaign::STATUS_DRAFT,
            'total_contacts' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        return redirect()->route('wt.campaigns.show', $campaign->id)
            ->with('success', 'Campanha criada. Configure e dispare quando estiver pronto.');
    }

    public function campaignsShow(int $id): View
    {
        $campaign = Campaign::where('user_id', auth()->id())->findOrFail($id);

        $messages = WhatsAppMessage::where('campaign_id', $campaign->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $deliveredCount = WhatsAppMessage::where('campaign_id', $campaign->id)
            ->whereIn('status', ['delivered', 'read'])
            ->count();

        $readCount = WhatsAppMessage::where('campaign_id', $campaign->id)
            ->where('status', 'read')
            ->count();

        return view('whatstrigger.campaigns.show', compact(
            'campaign',
            'messages',
            'deliveredCount',
            'readCount'
        ));
    }

    public function campaignsSend(Request $request, int $id): RedirectResponse
    {
        $campaign = Campaign::where('user_id', auth()->id())->findOrFail($id);

        abort_if(
            ! in_array($campaign->status, [Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED]),
            422,
            'Esta campanha não pode ser disparada.'
        );

        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
            // Agendada — apenas confirma o status
            $campaign->update(['status' => Campaign::STATUS_SCHEDULED]);

            return redirect()->route('wt.campaigns.show', $campaign->id)
                ->with('success', 'Campanha agendada para '.$campaign->scheduled_at->format('d/m/Y H:i').'.');
        }

        // Disparo imediato
        $campaign->update(['status' => Campaign::STATUS_SENDING]);
        DispatchCampaign::dispatch($campaign);

        return redirect()->route('wt.campaigns.show', $campaign->id)
            ->with('success', 'Campanha enviada para a fila de disparo.');
    }

    public function campaignsCancel(int $id): RedirectResponse
    {
        $campaign = Campaign::where('user_id', auth()->id())->findOrFail($id);

        abort_if(
            ! in_array($campaign->status, [Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED, Campaign::STATUS_SENDING]),
            422,
            'Esta campanha não pode ser cancelada.'
        );

        $campaign->update(['status' => Campaign::STATUS_CANCELLED]);

        return redirect()->route('wt.campaigns.show', $campaign->id)
            ->with('success', 'Campanha cancelada com sucesso.');
    }

    // =========================================================================
    // WHATSAPP
    // =========================================================================

    public function whatsappStatus(): JsonResponse
    {
        $connected = false;
        $qrUrl = null;

        try {
            $status = $this->evolutionApi->instanceStatus();
            $connected = isset($status[0]['connectionStatus']) && $status[0]['connectionStatus'] === 'open';

            if (! $connected) {
                $qrData = $this->evolutionApi->connectQrCode();
                $qrUrl = $qrData['qrcode']['base64'] ?? null;
            }
        } catch (\Throwable) {
        }

        return response()->json(compact('connected', 'qrUrl'));
    }

    public function whatsappConnect(): View
    {
        $connected = false;
        $qrUrl = null;
        $error = null;

        try {
            $status = $this->evolutionApi->instanceStatus();
            $connected = isset($status[0]['connectionStatus']) && $status[0]['connectionStatus'] === 'open';

            if (! $connected) {
                $qrData = $this->evolutionApi->connectQrCode();
                $qrUrl = $qrData['qrcode']['base64'] ?? null;
            }
        } catch (\Throwable $e) {
            $error = 'Não foi possível comunicar com a Evolution API: '.$e->getMessage();
        }

        return view('whatstrigger.whatsapp.connect', compact('connected', 'qrUrl', 'error'));
    }

    public function whatsappDisconnect(): RedirectResponse
    {
        try {
            $this->evolutionApi->disconnect();

            return redirect()->route('wt.whatsapp.connect')
                ->with('success', 'WhatsApp desconectado com sucesso.');
        } catch (\Throwable $e) {
            return redirect()->route('wt.whatsapp.connect')
                ->with('error', 'Erro ao desconectar: '.$e->getMessage());
        }
    }

    // =========================================================================
    // ASSINATURA
    // =========================================================================

    public function subscriptionIndex(): View
    {
        $subscription = Subscription::where('user_id', auth()->id())->latest()->first();

        $plans = [
            Subscription::PLAN_FREE => ['label' => 'Free',       'price' => 'Grátis',       'limit' => 50],
            Subscription::PLAN_STARTER => ['label' => 'Starter',    'price' => 'R$ 29,90/mês', 'limit' => 2000],
            Subscription::PLAN_PRO => ['label' => 'Pro',        'price' => 'R$ 79,90/mês', 'limit' => 10000],
            Subscription::PLAN_ENTERPRISE => ['label' => 'Enterprise', 'price' => 'R$ 497/mês',   'limit' => -1],
        ];

        return view('whatstrigger.subscription.index', compact('subscription', 'plans'));
    }

    // =========================================================================
    // FILA
    // =========================================================================

    public function queueMonitor(): View
    {
        $pending = 0;
        $redis = null;
        try {
            $redis = DB::connection('redis')->client();
            $pending = (int) $redis->llen('queues:default');
        } catch (\Throwable) {
        }

        $failed = DB::table('failed_jobs')
            ->latest('failed_at')
            ->paginate(20);

        $jobCounts = [
            'pending' => $pending,
            'failed' => DB::table('failed_jobs')->count(),
        ];

        return view('whatstrigger.queue.monitor', compact('failed', 'jobCounts'));
    }

    public function queueFailedRetry(int $id): RedirectResponse
    {
        $exitCode = Artisan::call('queue:retry', ['id' => [$id]]);

        if ($exitCode === 0) {
            return redirect()->route('wt.queue.monitor')
                ->with('success', 'Job reenviado para a fila com sucesso.');
        }

        return redirect()->route('wt.queue.monitor')
            ->with('error', 'Falha ao reenviar job para a fila.');
    }

    // =========================================================================
    // WEBHOOK LOGS
    // =========================================================================

    public function webhookLogs(Request $request): View
    {
        $query = WebhookLog::latest();

        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('whatstrigger.webhooks.logs', compact('logs'));
    }
}
