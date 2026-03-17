@extends('layouts.app')
@section('title', 'Contacts')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Contacts 👥</h1>
        <p class="page-subtitle">Manage your contact lists and subscribers</p>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:28px;">
    <div class="stat-card gold">
        <div class="stat-icon-bg">👥</div>
        <div class="stat-label">Total Contacts</div>
        <div class="stat-value">{{ number_format($totalContacts) }}</div>
        <div class="stat-meta">Across all lists</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon-bg">✅</div>
        <div class="stat-label">Subscribed</div>
        <div class="stat-value">{{ number_format($totalSubscribed) }}</div>
        <div class="stat-meta">Active subscribers</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon-bg">🚫</div>
        <div class="stat-label">Unsubscribed</div>
        <div class="stat-value">{{ number_format($totalUnsubscribed) }}</div>
        <div class="stat-meta">Opted out</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    {{-- Lists Grid --}}
    <div>
        @if($lists->count())
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
            @foreach($lists as $list)
            <div class="card" style="position:relative;transition:box-shadow .2s;">
                {{-- Progress bar: subscribed ratio --}}
                @php
                    $pct = $list->contacts_count > 0
                        ? round(($list->active_contacts_count / $list->contacts_count) * 100)
                        : 0;
                @endphp
                <div style="height:3px;background:var(--border);border-radius:3px 3px 0 0;overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;background:var(--gold);transition:width .4s;"></div>
                </div>

                <div style="padding:20px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;">
                        <div>
                            <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:4px;">
                                {{ $list->name }}
                            </h3>
                            @if($list->description)
                            <p style="font-size:12px;color:var(--muted);line-height:1.5;">{{ $list->description }}</p>
                            @endif
                        </div>
                        <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--ink);">
                            {{ number_format($list->contacts_count) }}
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;margin-bottom:16px;font-size:12px;">
                        <span style="display:flex;align-items:center;gap:4px;color:var(--green);">
                            <span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;"></span>
                            {{ number_format($list->active_contacts_count) }} subscribed
                        </span>
                        <span style="color:var(--muted);">·</span>
                        <span style="color:var(--muted);">
                            {{ number_format($list->contacts_count - $list->active_contacts_count) }} unsub
                        </span>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center;">
                        <a href="{{ route('contacts.show', $list) }}" class="btn btn-primary btn-sm">
                            👥 View Contacts
                        </a>
                        <a href="{{ route('contacts.import.form', $list) }}" class="btn btn-secondary btn-sm">
                            📥 Import
                        </a>
                        <div style="margin-left:auto;display:flex;gap:4px;">
                            <button onclick="openEditList({{ $list->id }}, '{{ addslashes($list->name) }}', '{{ addslashes($list->description ?? '') }}')"
                                class="btn btn-secondary btn-sm">✏️</button>
                            <form method="POST" action="{{ route('contacts.list.destroy', $list) }}"
                                onsubmit="return confirm('Delete list and ALL {{ $list->contacts_count }} contacts?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div style="padding:10px 20px;border-top:1px solid var(--border);font-size:11px;color:var(--muted);">
                    Created {{ $list->created_at->format('M j, Y') }}
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="card" style="text-align:center;padding:60px 24px;">
            <div style="font-size:48px;margin-bottom:16px;">👥</div>
            <h3 style="font-family:'Syne',sans-serif;font-size:18px;margin-bottom:8px;">No contact lists yet</h3>
            <p style="color:var(--muted);font-size:14px;">Create your first list to start managing subscribers.</p>
        </div>
        @endif
    </div>

    {{-- Create List Sidebar --}}
    <div style="position:sticky;top:72px;display:flex;flex-direction:column;gap:16px;">

        {{-- Create new list --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">➕</div>
                <div><h3>New List</h3><p>Create a contact list</p></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('contacts.list.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>List Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="e.g. Newsletter Subscribers"
                            class="{{ $errors->has('name') ? 'invalid' : '' }}">
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" value="{{ old('description') }}"
                            placeholder="Optional description">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                        ➕ Create List
                    </button>
                </form>
            </div>
        </div>

        {{-- CSV Format guide --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <div><h3>CSV Format</h3><p>For bulk import</p></div>
            </div>
            <div class="card-body" style="padding:16px 20px;">
                <p style="font-size:13px;color:var(--muted);margin-bottom:12px;line-height:1.6;">
                    Your CSV file must have a header row. Supported columns:
                </p>
                <code style="display:block;background:var(--ink);color:var(--gold);padding:12px 14px;border-radius:8px;font-size:11px;line-height:1.9;font-family:'Courier New',monospace;">email,name,company
john@example.com,John Doe,Acme
jane@example.com,Jane Smith,</code>
                <p style="font-size:11px;color:var(--muted);margin-top:10px;line-height:1.5;">
                    Only <strong>email</strong> is required. Duplicates are automatically skipped.
                </p>
            </div>
        </div>

    </div>
</div>

{{-- Edit List Modal --}}
<div id="editListModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:20px;">
        <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:20px;">Edit List</h3>
        <form method="POST" id="editListForm">
            @csrf @method('PATCH')
            <div class="form-group">
                <label>List Name <span class="req">*</span></label>
                <input type="text" name="name" id="editListName" placeholder="List name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" id="editListDesc" placeholder="Optional">
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="closeEditList()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditList(id, name, desc) {
    document.getElementById('editListForm').action = '/contacts/lists/' + id;
    document.getElementById('editListName').value = name;
    document.getElementById('editListDesc').value = desc;
    const modal = document.getElementById('editListModal');
    modal.style.display = 'flex';
}
function closeEditList() {
    document.getElementById('editListModal').style.display = 'none';
}
document.getElementById('editListModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditList();
});
</script>
@endpush
