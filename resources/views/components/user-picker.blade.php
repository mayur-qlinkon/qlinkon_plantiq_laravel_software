{{--
    User Picker
    ───────────
    A searchable replacement for a user <select>. A team of thirty makes a plain
    dropdown a scrolling exercise, so this filters by name or email as you type
    and keeps a hidden input carrying the id.

    Works two ways:
      • Plain forms  → the hidden input submits under {{ $name }}.
      • Alpine forms → pass x-model to bind the id into your own state.

    USAGE
    ─────
    <x-user-picker
        name="assigned_to"
        :users="$users"
        :selected="$lead->assigned_user_id"
        placeholder="Search team member..."
    />

    <x-user-picker name="assigned_to" :users="$users" x-model="taskForm.assigned_to" />

    PROPS
    ─────
    users        array/collection  required — [['id' =>, 'name' =>, 'email' =>], ...]
    name         string            required — form field name
    selected     mixed             null     — pre-selected user id
    placeholder  string            'Search team member...'
    label        string            null     — rendered above when given
    required     bool              false
    allowClear   bool              true     — shows an X to unassign
    xModel       string            null     — Alpine expression to bind the id to
--}}

@props ([
    'users' => [],
    'name' => 'assigned_to',
    'selected' => null,
    'placeholder' => 'Search team member...',
    'label' => null,
    'required' => false,
    'allowClear' => true,
    'xModel' => null,
])

@php
    $list = collect($users)
        ->map(fn ($u) => [
            'id' => (string) (is_array($u) ? $u['id'] : $u->id),
            'name' => is_array($u) ? $u['name'] : $u->name,
            'email' => is_array($u) ? ($u['email'] ?? '') : ($u->email ?? ''),
        ])
        ->values();

    $currentValue = (string) (old($name, $selected) ?? '');
@endphp

<div
    x-data="userPicker({
        users: @js($list),
        initial: @js($currentValue),
        @if ($xModel) bindTo: (id) => { {{ $xModel }} = id; }, @endif
    })"
    @if ($xModel)
        x-effect="syncFrom({{ $xModel }})"
    @endif
    class="relative w-full"
    @click.outside="open = false"
    @keydown.escape="open = false"
    {{-- The value lives in Alpine state, so a filter bar cannot reset this by
         writing to the DOM. It broadcasts instead and we clear ourselves. --}}
    @filters-cleared.window="clear()"
>
    @if ($label)
        <span class="mb-1 block text-[10px] font-bold text-gray-400">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </span>
    @endif

    {{-- The value that actually travels with the form, and the only element
         allowed to emit change. Parent code reads $event.target.value, so the
         event has to come from the field that holds the id. --}}
    <input type="hidden" x-ref="field" name="{{ $name }}" :value="selectedId" />

    <div class="relative">
        <input
            type="text"
            x-model="term"
            @focus="open = true"
            @input="
                open = true;
                selectedId = '';
            "
            {{-- A text input fires a native change on blur. That bubbled out of
                 this component and wrote the typed text into the parent's
                 assigned_to, which the server then rejected as a missing user. --}}
            @change.stop
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            {{ $attributes->merge(['class' => 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 pr-8 text-[12px] font-semibold text-gray-700 outline-none transition-all focus:border-green-400 focus:bg-white']) }}
        />

        <button
            type="button"
            x-show="selectedId && {{ $allowClear ? 'true' : 'false' }}"
            x-cloak
            @click="clear()"
            class="absolute top-1/2 right-2.5 -translate-y-1/2 text-gray-400 transition-colors hover:text-red-500"
            title="Clear"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>

        <svg
            x-show="!selectedId"
            class="pointer-events-none absolute top-1/2 right-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-300"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
        >
            <path d="m6 9 6 6 6-6" />
        </svg>
    </div>

    <ul
        x-show="open"
        x-cloak
        x-transition
        class="absolute top-full left-0 z-50 mt-1 max-h-56 w-full overflow-y-auto overscroll-contain rounded-xl border border-gray-200 bg-white shadow-2xl"
    >
        <li x-show="matches.length === 0" class="px-3 py-4 text-center text-[12px] font-medium text-gray-500">
            No team member matches that.
        </li>

        <template x-for="user in matches" :key="user.id">
            <li
                @click="select(user)"
                class="cursor-pointer border-b border-gray-50 px-3 py-2 transition-colors last:border-0 hover:bg-gray-50"
                :class="user.id === selectedId ? 'bg-green-50' : ''"
            >
                <div class="text-[12px] font-bold text-gray-800" x-text="user.name"></div>
                <div class="text-[10px] text-gray-400" x-show="user.email" x-text="user.email"></div>
            </li>
        </template>
    </ul>
</div>

@once
    @push ('scripts')
        <script>
            window.userPicker = function ({ users = [], initial = "", bindTo = null }) {
                return {
                    users: users,
                    open: false,
                    term: "",
                    selectedId: "",

                    init() {
                        if (initial) this.applyId(initial);
                    },

                    get matches() {
                        const q = this.term.trim().toLowerCase();

                        if (q === "") return this.users;

                        return this.users.filter(
                            (u) => u.name.toLowerCase().includes(q) || (u.email && u.email.toLowerCase().includes(q)),
                        );
                    },

                    applyId(id) {
                        const user = this.users.find((u) => String(u.id) === String(id));

                        this.selectedId = user ? user.id : "";
                        this.term = user ? user.name : "";
                    },

                    /**
                     * Keeps the box in step when the bound Alpine value changes
                     * from outside — opening an edit modal, or resetting a form.
                     */
                    syncFrom(id) {
                        const next = id ? String(id) : "";

                        if (next === this.selectedId) return;

                        this.applyId(next);
                    },

                    select(user) {
                        this.selectedId = user.id;
                        this.term = user.name;
                        this.open = false;

                        if (bindTo) bindTo(user.id);

                        this.emitChange();
                    },

                    clear() {
                        this.selectedId = "";
                        this.term = "";

                        if (bindTo) bindTo("");

                        this.emitChange();
                    },

                    /**
                     * Fired from the hidden field so anything listening upstream
                     * reads an id, never the text someone happened to type.
                     */
                    emitChange() {
                        this.$nextTick(() => {
                            this.$refs.field.dispatchEvent(new Event("change", { bubbles: true }));
                        });
                    },
                };
            };
        </script>
    @endpush
@endonce
