<?php

namespace App\Http\Controllers;

use App\Models\AlarmEvent;
use App\Models\AlarmRule;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlarmController extends Controller
{
    // ── Active events ─────────────────────────────────────────────────────

    public function aktivni(): View
    {
        $events = AlarmEvent::query()
            ->with(['rule:id,name,type', 'vehicle:id,name,plate,brand,fuel_type'])
            ->whereNull('resolved_at')
            ->orderByDesc('triggered_at')
            ->paginate(30);
        return view('alarmy.aktivni', compact('events'));
    }

    public function historie(): View
    {
        $events = AlarmEvent::query()
            ->with(['rule:id,name,type', 'vehicle:id,name,plate,brand,fuel_type'])
            ->whereNotNull('resolved_at')
            ->orderByDesc('triggered_at')
            ->paginate(30);
        return view('alarmy.historie', compact('events'));
    }

    public function resolve(AlarmEvent $event): RedirectResponse
    {
        $event->update(['resolved_at' => now()]);
        return back()->with('status', 'Alarm vyřešen.');
    }

    // ── Rules CRUD ───────────────────────────────────────────────────────

    public function rules(): View
    {
        $rules = AlarmRule::with('vehicle:id,name,plate,brand,fuel_type')->orderBy('type')->orderBy('name')->get();
        return view('alarmy.pravidla', compact('rules'));
    }

    public function ruleCreate(): View
    {
        return view('alarmy.pravidlo-form', [
            'rule'     => null,
            'types'    => AlarmRule::TYPES,
            'severities' => AlarmRule::SEVERITIES,
            'vehicles' => $this->vehicleOptions(),
        ]);
    }

    public function ruleStore(Request $request): RedirectResponse
    {
        AlarmRule::create($this->validateRule($request));
        return redirect()->route('alarmy.pravidla')->with('status', 'Pravidlo vytvořeno.');
    }

    public function ruleEdit(AlarmRule $rule): View
    {
        return view('alarmy.pravidlo-form', [
            'rule'       => $rule,
            'types'      => AlarmRule::TYPES,
            'severities' => AlarmRule::SEVERITIES,
            'vehicles'   => $this->vehicleOptions(),
        ]);
    }

    public function ruleUpdate(Request $request, AlarmRule $rule): RedirectResponse
    {
        $rule->update($this->validateRule($request));
        return redirect()->route('alarmy.pravidla')->with('status', 'Pravidlo upraveno.');
    }

    public function ruleDestroy(AlarmRule $rule): RedirectResponse
    {
        $rule->delete();
        return redirect()->route('alarmy.pravidla')->with('status', 'Pravidlo smazáno.');
    }

    protected function vehicleOptions(): array
    {
        return Vehicle::where('active', true)->orderBy('name')->get(['id', 'name', 'plate'])
            ->mapWithKeys(fn ($v) => [$v->id => "{$v->name} ({$v->plate})"])->all();
    }

    protected function validateRule(Request $request): array
    {
        $request->merge([
            'active'        => $request->boolean('active'),
            'vehicle_id'    => $request->input('vehicle_id') ?: null,
            'notify_emails' => array_filter(array_map('trim', explode(',', (string) $request->input('notify_emails_csv', '')))) ?: null,
        ]);

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'type'          => ['required', Rule::in(array_keys(AlarmRule::TYPES))],
            'vehicle_id'    => ['nullable', 'integer', Rule::exists('vehicles', 'id')],
            'severity'      => ['required', Rule::in(array_keys(AlarmRule::SEVERITIES))],
            'cooldown_min'  => ['required', 'integer', 'min:0', 'max:1440'],
            'active'        => ['boolean'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'notify_emails' => ['nullable', 'array'],
            'notify_emails.*' => ['email'],
        ]);

        // Type-specific config — capture all "config_*" inputs
        $config = [];
        foreach ($request->all() as $k => $v) {
            if (str_starts_with($k, 'config_') && $v !== '' && $v !== null) {
                $config[substr($k, 7)] = is_numeric($v) ? (str_contains($v, '.') ? (float) $v : (int) $v) : $v;
            }
        }
        $data['config'] = $config ?: null;

        return $data;
    }
}
