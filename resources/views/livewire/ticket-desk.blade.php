@php
    $t = fn (string $key, array $replace = []) => __('simplify-hosting::tickets.'.$key, $replace);
    $staff = $this->staff();
    $statusLabel = fn (string $status) => $t($status === 'answered' ? ($staff ? 'status_waiting' : 'status_answered') : 'status_'.$status);
    $statusColor = fn (string $status) => ['open' => 'info', 'answered' => $staff ? 'warning' : 'success', 'closed' => 'gray'][$status] ?? 'gray';
    $priorityLabel = fn (string $priority) => $t('priority_'.$priority);
    $name = fn ($user) => $user?->username ?? $t('deleted_user');
@endphp
<div class="sh-desk">
    @once
        <style>@include('simplify-hosting::partials.tickets-css')</style>
    @endonce

    @if ($current !== null)
        {{-- One conversation. --}}
        <x-filament::section>
            <x-slot name="heading">#{{ $current->id }} {{ $current->subject }}</x-slot>
            <x-slot name="description">
                {{ $statusLabel($current->status) }}@if ($current->server) · {{ $current->server->name }}@elseif ($current->server_id) · {{ $t('deleted_server') }}@endif
                @if ($current->category) · {{ $current->category }}@endif
                · {{ $t('priority') }}: {{ $priorityLabel($current->priority) }}
            </x-slot>
            <x-slot name="afterHeader">
                <x-filament::button color="gray" icon="tabler-arrow-left" wire:click="back">{{ $t('back') }}</x-filament::button>
                @if ($staff && $current->server)
                    <x-filament::button color="gray" icon="tabler-external-link" tag="a" :href="\App\Filament\Server\Pages\Console::getUrl(panel: 'server', tenant: $current->server)">{{ $t('open_server') }}</x-filament::button>
                @endif
                @if ($current->status !== 'closed')
                    <x-filament::button color="gray" icon="tabler-x" wire:click="close">{{ $t('close') }}</x-filament::button>
                @endif
            </x-slot>

            <div @class(['sh-ticket', 'has-side' => $staff])>
                <div class="sh-thread">
                    @foreach ($messages as $note)
                        <article wire:key="message-{{ $note->id }}" @class(['sh-msg', 'is-staff' => $note->staff, 'is-note' => $note->internal])>
                            <p class="sh-meta">
                                <strong>{{ $note->user_id === auth()->id() ? $t('you') : $name($note->user) }}</strong>
                                @if ($note->internal)
                                    <x-filament::badge color="warning" size="sm">{{ $t('internal_note') }}</x-filament::badge>
                                @elseif ($note->staff)
                                    <x-filament::badge color="gray" size="sm">{{ $t('support_badge') }}</x-filament::badge>
                                @endif
                                <time datetime="{{ $note->created_at?->toIso8601String() }}">{{ $note->created_at?->diffForHumans() }}</time>
                            </p>
                            <div class="sh-text">{{ $note->body }}</div>
                            @if ($note->log)
                                <pre class="sh-log">{{ $note->log }}</pre>
                            @endif
                        </article>
                    @endforeach

                    <form class="sh-reply" wire:submit="send">
                        @if ($staff)
                            <x-filament::tabs>
                                <x-filament::tabs.item :active="! $internal" wire:click="$set('internal', false)">{{ $t('reply_customer') }}</x-filament::tabs.item>
                                <x-filament::tabs.item :active="$internal" wire:click="$set('internal', true)">{{ $t('internal_note') }}</x-filament::tabs.item>
                            </x-filament::tabs>
                        @endif
                        <label class="sh-label" for="sh-reply">{{ $t('reply') }}</label>
                        <x-filament::input.wrapper :valid="! $errors->has('reply')">
                            <textarea id="sh-reply" class="fi-input sh-area" wire:model="reply" rows="4" maxlength="{{ \Simplify\Hosting\Services\Tickets::BODY }}"
                                placeholder="{{ $staff ? ($internal ? $t('note_placeholder') : $t('reply_placeholder_staff')) : $t('reply_placeholder') }}"></textarea>
                        </x-filament::input.wrapper>
                        @error('reply') <p class="sh-error">{{ $message }}</p> @enderror
                        <div class="sh-row">
                            <span class="sh-hint">{{ $staff ? ($internal ? $t('note_hint') : $t('reply_hint_staff')) : $t('reply_hint') }}</span>
                            <x-filament::button type="submit" icon="tabler-send">{{ $staff && $internal ? $t('save_note') : $t('send_reply') }}</x-filament::button>
                        </div>
                    </form>
                </div>

                @if ($staff)
                    <aside class="sh-side">
                        <label class="sh-label" for="sh-status">{{ $t('status') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="sh-status" wire:change="status($event.target.value)">
                                @foreach (\Simplify\Hosting\Models\Ticket::STATUSES as $status)
                                    <option value="{{ $status }}" @selected($current->status === $status)>{{ $statusLabel($status) }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <label class="sh-label" for="sh-priority">{{ $t('priority') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="sh-priority" wire:change="prioritize($event.target.value)">
                                @foreach (\Simplify\Hosting\Models\Ticket::PRIORITIES as $level)
                                    <option value="{{ $level }}" @selected($current->priority === $level)>{{ $priorityLabel($level) }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <label class="sh-label" for="sh-assign">{{ $t('assigned_to') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select id="sh-assign" wire:change="assign($event.target.value)">
                                <option value="">{{ $t('nobody') }}</option>
                                @foreach ($staffNames as $id => $username)
                                    <option value="{{ $id }}" @selected($current->assigned_to === $id)>{{ $username }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <dl class="sh-facts">
                            <dt>{{ $t('customer') }}</dt><dd>{{ $name($current->user) }}@if ($current->user) · {{ $current->user->email }}@endif</dd>
                            <dt>{{ $t('server') }}</dt><dd>{{ $current->server?->name ?? ($current->server_id ? $t('deleted_server') : $t('no_server')) }}</dd>
                            <dt>{{ $t('opened') }}</dt><dd>{{ $current->created_at?->diffForHumans() }}</dd>
                        </dl>
                    </aside>
                @endif
            </div>
        </x-filament::section>
    @elseif ($new)
        {{-- A new ticket. --}}
        <form wire:submit="create">
            <x-filament::section>
                <x-slot name="heading">{{ $t('new') }}</x-slot>
                <x-slot name="description">{{ $intro ?: $t('new_help') }}</x-slot>
                <x-slot name="afterHeader">
                    <x-filament::button color="gray" wire:click="back">{{ $t('cancel') }}</x-filament::button>
                    <x-filament::button type="submit" icon="tabler-send">{{ $t('send') }}</x-filament::button>
                </x-slot>
                <div class="sh-form">
                    <div class="sh-field">
                        <label class="sh-label" for="sh-subject">{{ $t('subject') }}</label>
                        <x-filament::input.wrapper :valid="! $errors->has('subject')">
                            <x-filament::input id="sh-subject" type="text" wire:model="subject" maxlength="{{ \Simplify\Hosting\Services\Tickets::SUBJECT }}" :placeholder="$t('subject_placeholder')" autocomplete="off" />
                        </x-filament::input.wrapper>
                        @error('subject') <p class="sh-error">{{ $message }}</p> @enderror
                    </div>
                    @if ($categories !== [])
                        <div class="sh-field">
                            <label class="sh-label" for="sh-category">{{ $t('category') }}</label>
                            <x-filament::input.wrapper :valid="! $errors->has('category')">
                                <x-filament::input.select id="sh-category" wire:model="category">
                                    <option value="">{{ $t('pick') }}</option>
                                    @foreach ($categories as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('category') <p class="sh-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div class="sh-field">
                        <label class="sh-label" for="sh-new-priority">{{ $t('priority') }}</label>
                        <x-filament::input.wrapper :valid="! $errors->has('priority')">
                            <x-filament::input.select id="sh-new-priority" wire:model="priority">
                                @foreach (\Simplify\Hosting\Models\Ticket::PRIORITIES as $level)
                                    <option value="{{ $level }}">{{ $priorityLabel($level) }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('priority') <p class="sh-error">{{ $message }}</p> @enderror
                    </div>
                    @if ($this->mode === 'account')
                        <div class="sh-field">
                            <label class="sh-label" for="sh-about">{{ $t('server') }}</label>
                            <x-filament::input.wrapper :valid="! $errors->has('about')">
                                <x-filament::input.select id="sh-about" wire:model="about">
                                    <option value="">{{ $withoutServer ? $t('no_server') : $t('pick') }}</option>
                                    @foreach ($servers as $id => $serverName)
                                        <option value="{{ $id }}">{{ $serverName }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            @error('about') <p class="sh-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div class="sh-field is-wide">
                        <label class="sh-label" for="sh-message">{{ $t('message') }}</label>
                        <x-filament::input.wrapper :valid="! $errors->has('message')">
                            <textarea id="sh-message" class="fi-input sh-area" wire:model="message" rows="6" maxlength="{{ \Simplify\Hosting\Services\Tickets::BODY }}" placeholder="{{ $t('message_placeholder') }}"></textarea>
                        </x-filament::input.wrapper>
                        @error('message') <p class="sh-error">{{ $message }}</p> @enderror
                    </div>
                    @if ($log !== '')
                        <div class="sh-field is-wide">
                            <p class="sh-label">{{ $t('attached') }}</p>
                            <pre class="sh-log">{{ $log }}</pre>
                            <div><x-filament::button color="gray" size="sm" icon="tabler-x" wire:click="$set('log', '')">{{ $t('remove') }}</x-filament::button></div>
                        </div>
                    @endif
                    @if ($canLog)
                        <label class="sh-check is-wide">
                            <x-filament::input.checkbox wire:model="attachLog" />
                            <span>{{ $t('attach_log') }}<span class="sh-hint">{{ $t('attach_log_help') }}</span></span>
                        </label>
                    @endif
                </div>
            </x-filament::section>
        </form>
    @else
        {{-- The list. --}}
        <x-filament::section>
            <x-slot name="heading">{{ $staff ? $t('title_staff') : ($this->mode === 'server' ? $t('title_server') : $t('title_account')) }}</x-slot>
            <x-slot name="description">{{ $staff ? $t('subtitle_staff') : $t('subtitle_customer') }}</x-slot>
            <x-slot name="afterHeader">
                @if ($staff)
                    <x-filament::input.wrapper prefix-icon="tabler-search" class="sh-search">
                        <x-filament::input type="search" wire:model.live.debounce.300ms="search" :placeholder="$t('search')" :aria-label="$t('search')" autocomplete="off" />
                    </x-filament::input.wrapper>
                @else
                    <x-filament::button icon="tabler-plus" wire:click="start">{{ $t('new') }}</x-filament::button>
                @endif
            </x-slot>

            @if ($staff)
                <x-filament::tabs class="sh-filters">
                    @foreach (['open' => 'status_open', 'answered' => 'status_waiting', 'closed' => 'status_closed', 'mine' => 'mine', 'all' => 'all'] as $key => $label)
                        <x-filament::tabs.item :active="$filter === $key" wire:click="setFilter('{{ $key }}')" :badge="in_array($key, ['open', 'mine'], true) && ($counts[$key] ?? 0) > 0 ? $counts[$key] : null">{{ $t($label) }}</x-filament::tabs.item>
                    @endforeach
                </x-filament::tabs>
            @endif

            @if ($tickets->isEmpty())
                <p class="sh-empty">{{ $staff ? $t('empty_staff') : $t('empty_customer') }}</p>
            @else
                <div class="sh-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th><th>{{ $t('subject') }}</th>
                                @if ($staff)<th>{{ $t('customer') }}</th>@endif
                                @if ($this->mode !== 'server')<th>{{ $t('server') }}</th>@endif
                                @if ($staff)<th>{{ $t('assigned_to') }}</th>@endif
                                <th>{{ $t('priority') }}</th><th>{{ $t('status') }}</th><th>{{ $t('last_reply') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $row)
                                @php($unread = $staff ? $row->staff_unread : $row->customer_unread)
                                <tr wire:key="ticket-{{ $row->id }}" wire:click="show({{ $row->id }})" @class(['is-unread' => $unread && $row->status !== 'closed'])>
                                    <td class="sh-num">{{ $row->id }}</td>
                                    <td>
                                        <button type="button" class="sh-link" wire:click.stop="show({{ $row->id }})">{{ $row->subject }}</button>
                                        @if ($row->category)<span class="sh-sub">{{ $row->category }}</span>@endif
                                    </td>
                                    @if ($staff)<td>{{ $name($row->user) }}</td>@endif
                                    @if ($this->mode !== 'server')<td>{{ $row->server?->name ?? ($row->server_id ? $t('deleted_server') : $t('no_server')) }}</td>@endif
                                    @if ($staff)<td>{{ $row->assignee?->username ?? $t('nobody') }}</td>@endif
                                    <td><x-filament::badge :color="['high' => 'warning', 'normal' => 'gray', 'low' => 'gray'][$row->priority] ?? 'gray'" size="sm">{{ $priorityLabel($row->priority) }}</x-filament::badge></td>
                                    <td><x-filament::badge :color="$statusColor($row->status)" size="sm">{{ $statusLabel($row->status) }}</x-filament::badge></td>
                                    <td class="sh-num">{{ $row->last_reply_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    @endif
</div>
