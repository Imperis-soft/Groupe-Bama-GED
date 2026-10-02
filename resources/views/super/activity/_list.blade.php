@if($logs->isEmpty())
    <div class="empty">
        <div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <p class="text-sm font-bold text-slate-900">Aucune activité</p>
        <p class="text-sm text-slate-500 mt-1">Les actions effectuées sur la plateforme apparaîtront ici.</p>
    </div>
@else
    <ul class="px-6 py-2">
        @foreach($logs as $log)
            @php
                [$icon, $tone] = match(true) {
                str_starts_with($log->action, 'subscription') => ['fa-receipt', 'bg-emerald-50 text-emerald-600 ring-emerald-100'],
                $log->action === 'organization_settings_updated' => ['fa-sliders', 'bg-slate-100 text-slate-600 ring-slate-200'],
                str_starts_with($log->action, 'plan')         => ['fa-layer-group', 'bg-violet-50 text-violet-600 ring-violet-100'],
                str_starts_with($log->action, 'user')         => ['fa-user', 'bg-sky-50 text-sky-600 ring-sky-100'],
                in_array($log->action, ['organization_entered', 'organization_left']) => ['fa-door-open', 'bg-amber-50 text-amber-600 ring-amber-100'],
                default                                       => ['fa-building', 'bg-orange-50 text-orange-600 ring-orange-100'],
            };
            @endphp
            <li class="relative flex gap-4 py-3.5">
                @unless($loop->last)<span class="absolute left-[15px] top-12 bottom-0 w-px bg-slate-100"></span>@endunless
                <span class="relative w-8 h-8 rounded-full ring-1 flex items-center justify-center shrink-0 {{ $tone }}">
                    <i class="fa-solid {{ $icon }} text-[11px]"></i>
                </span>
                <div class="min-w-0 flex-1 pt-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                        <p class="text-sm text-slate-800">{{ $log->description }}</p>
                        <time class="text-xs text-slate-400 whitespace-nowrap" title="{{ $log->created_at->format('d/m/Y H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        <span class="font-medium text-slate-600">{{ $log->user?->full_name ?? 'Système' }}</span>
                        @if($log->organization) · <a href="{{ route('super.organizations.show', $log->organization) }}" class="hover:text-orange-600">{{ $log->organization->name }}</a>@endif
                        @if($log->ip_address) · <span class="font-mono text-[11px]">{{ $log->ip_address }}</span>@endif
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
@endif
