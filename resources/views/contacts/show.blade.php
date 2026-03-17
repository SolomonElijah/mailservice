@extends('layouts.app')
@section('title', $list->name)

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">{{ $list->name }}</h1>
        <p class="page-subtitle">
            {{ $list->contacts()->count() }} contacts total ·
            {{ $list->activeContacts()->count() }} subscribed
            @if($list->description) · {{ $list->description }} @endif
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ route('contacts.export', $list) }}" class="btn btn-secondary">📤 Export CSV</a>
        <a href="{{ route('contacts.import.form', $list) }}" class="btn btn-secondary">📥 Import CSV</a>
        <a href="{{ route('contacts.index') }}" class="btn btn-secondary">← All Lists</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start;">

    {{-- Contacts Table --}}
    <div>
        {{-- Filters + Search --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="padding:14px 20px;">
                <form method="GET" action="{{ route('contacts.show', $list) }}">
                    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <div style="flex:1;min-width:200px;">
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="🔍 Search by name, email or company..."
                                style="width:100%;">
                        </div>
                        <select name="status" style="padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;background:#fff;outline:none;min-width:140px;">
                            <option value="">All statuses</option>
                            <option value="subscribed"   {{ request('status') === 'subscribed'   ? 'selected' : '' }}>✅ Subscribed</option>
                            <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>🚫 Unsubscribed</option>
                            <option value="bounced"      {{ request('status') === 'bounced'      ? 'selected' : '' }}>⚠️ Bounced</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        @if(request()->hasAny(['search','status']))
                            <a href="{{ route('contacts.show', $list) }}" class="btn btn-secondary btn-sm">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="card-icon">👥</div>
                    <div>
                        <h3>Contacts</h3>
                        <p>{{ $contacts->total() }} result(s)</p>
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;" id="bulkActionsBar" style="display:none;">
                    <span id="selectedCount" style="font-size:13px;color:var(--muted);"></span>
                    <form method="POST" action="{{ route('contacts.bulk', $list) }}" id="bulkForm">
                        @csrf
                        <input type="hidden" name="contact_ids" id="bulkIds">
                        <select name="bulk_action" style="padding:7px 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:12px;background:#fff;outline:none;">
                            <option value="">Bulk action...</option>
                            <option value="unsubscribe">🚫 Unsubscribe</option>
                            <option value="resubscribe">✅ Resubscribe</option>
                            <option value="delete">🗑 Delete</option>
                        </select>
                        <button type="button" onclick="applyBulk()" class="btn btn-secondary btn-sm">Apply</button>
                    </form>
                </div>
            </div>

            <div class="table-wrap">
                @if($contacts->count())
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px;">
                                <input type="checkbox" id="selectAll" onchange="toggleAll(this)"
                                    style="accent-color:var(--gold);width:15px;height:15px;">
                            </th>
                            <th>Contact</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $contact)
                        <tr>
                            <td>
                                <input type="checkbox" class="contact-checkbox" value="{{ $contact->id }}"
                                    onchange="updateBulkBar()"
                                    style="accent-color:var(--gold);width:15px;height:15px;">
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:8px;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--gold);flex-shrink:0;">
                                        {{ strtoupper(substr($contact->name ?? $contact->email, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:500;font-size:13.5px;">{{ $contact->name ?: '—' }}</div>
                                        <div style="font-size:12px;color:var(--muted);">{{ $contact->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:13px;color:var(--muted);">{{ $contact->company ?: '—' }}</td>
                            <td>
                                @if($contact->status === 'subscribed')
                                    <span class="badge badge-sent">✅ Subscribed</span>
                                @elseif($contact->status === 'unsubscribed')
                                    <span class="badge badge-failed">🚫 Unsubscribed</span>
                                @else
                                    <span class="badge badge-bounced">⚠️ Bounced</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                                {{ $contact->created_at->format('M j, Y') }}
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <button onclick="openEditContact({{ $contact->id }}, '{{ addslashes($contact->email) }}', '{{ addslashes($contact->name ?? '') }}', '{{ addslashes($contact->company ?? '') }}', '{{ $contact->status }}')"
                                        class="btn btn-secondary btn-sm">✏️</button>
                                    <form method="POST" action="{{ route('contacts.destroy', [$list, $contact]) }}"
                                        onsubmit="return confirm('Remove this contact?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Pagination --}}
                @if($contacts->hasPages())
                <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <span style="font-size:13px;color:var(--muted);">
                        Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} of {{ $contacts->total() }}
                    </span>
                    <div style="display:flex;gap:4px;">
                        @if($contacts->onFirstPage())
                            <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">← Prev</span>
                        @else
                            <a href="{{ $contacts->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                        @endif
                        @if($contacts->hasMorePages())
                            <a href="{{ $contacts->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                        @else
                            <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">Next →</span>
                        @endif
                    </div>
                </div>
                @endif

                @else
                <div style="text-align:center;padding:50px 24px;color:var(--muted);">
                    <div style="font-size:40px;margin-bottom:12px;">📭</div>
                    <p style="font-size:15px;margin-bottom:8px;color:var(--ink);">No contacts found</p>
                    <p style="font-size:13px;">
                        @if(request()->hasAny(['search','status']))
                            Try adjusting your search or <a href="{{ route('contacts.show', $list) }}" style="color:var(--gold);">clear filters</a>
                        @else
                            Add contacts manually or <a href="{{ route('contacts.import.form', $list) }}" style="color:var(--gold);">import a CSV</a>
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Add Contact Sidebar --}}
    <div style="position:sticky;top:72px;display:flex;flex-direction:column;gap:16px;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">➕</div>
                <div><h3>Add Contact</h3><p>Single contact entry</p></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('contacts.store', $list) }}">
                    @csrf
                    <div class="form-group">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            placeholder="contact@example.com"
                            inputmode="email"
                            class="{{ $errors->has('email') ? 'invalid' : '' }}">
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" value="{{ old('company') }}" placeholder="Company name">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        ➕ Add Contact
                    </button>
                </form>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">📊</div>
                <div><h3>List Stats</h3></div>
            </div>
            <div class="card-body" style="padding:16px 20px;">
                @php
                    $total  = $list->contacts()->count();
                    $sub    = $list->activeContacts()->count();
                    $unsub  = $list->contacts()->where('status','unsubscribed')->count();
                    $bounce = $list->contacts()->where('status','bounced')->count();
                    $pct    = $total > 0 ? round(($sub / $total) * 100) : 0;
                @endphp
                @foreach([
                    ['Total',        $total,  '#0d0d14'],
                    ['Subscribed',   $sub,    'var(--green)'],
                    ['Unsubscribed', $unsub,  'var(--red)'],
                    ['Bounced',      $bounce, '#f59e0b'],
                ] as [$label, $val, $color])
                <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border);font-size:13.5px;">
                    <span style="color:var(--muted);">{{ $label }}</span>
                    <strong style="color:{{ $color }};font-family:'Syne',sans-serif;">{{ number_format($val) }}</strong>
                </div>
                @endforeach
                <div style="padding:12px 0 4px;">
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px;">
                        <span>Health score</span>
                        <span style="color:var(--green);font-weight:700;">{{ $pct }}%</span>
                    </div>
                    <div style="height:6px;background:var(--border);border-radius:6px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:var(--green);border-radius:6px;transition:width .4s;"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Edit Contact Modal --}}
<div id="editContactModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:20px;">
        <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:20px;">Edit Contact</h3>
        <form method="POST" id="editContactForm">
            @csrf @method('PATCH')
            <div class="form-group">
                <label>Email <span class="req">*</span></label>
                <input type="email" name="email" id="editEmail" placeholder="email@example.com">
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" id="editName" placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Company</label>
                <input type="text" name="company" id="editCompany" placeholder="Company">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="editStatus" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;background:#fff;outline:none;">
                    <option value="subscribed">✅ Subscribed</option>
                    <option value="unsubscribed">🚫 Unsubscribed</option>
                    <option value="bounced">⚠️ Bounced</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="closeEditContact()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Edit contact modal
function openEditContact(id, email, name, company, status) {
    document.getElementById('editContactForm').action = '/contacts/{{ $list->id }}/contacts/' + id;
    document.getElementById('editEmail').value   = email;
    document.getElementById('editName').value    = name;
    document.getElementById('editCompany').value = company;
    document.getElementById('editStatus').value  = status;
    const modal = document.getElementById('editContactModal');
    modal.style.display = 'flex';
}
function closeEditContact() {
    document.getElementById('editContactModal').style.display = 'none';
}
document.getElementById('editContactModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditContact();
});

// Bulk selection
function toggleAll(master) {
    document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}
function updateBulkBar() {
    const checked = document.querySelectorAll('.contact-checkbox:checked');
    const bar = document.getElementById('bulkActionsBar');
    const count = document.getElementById('selectedCount');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        count.textContent = checked.length + ' selected';
    } else {
        bar.style.display = 'none';
    }
}
function applyBulk() {
    const action = document.querySelector('[name="bulk_action"]').value;
    if (!action) { alert('Please select an action.'); return; }
    const ids = Array.from(document.querySelectorAll('.contact-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) { alert('No contacts selected.'); return; }
    if (action === 'delete' && !confirm('Delete ' + ids.length + ' contact(s)?')) return;
    document.getElementById('bulkIds').value = ids.join(',');
    document.getElementById('bulkForm').submit();
}
</script>
@endpush
