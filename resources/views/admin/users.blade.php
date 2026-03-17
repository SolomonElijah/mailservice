@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Users 👥</h1>
        <p class="page-subtitle">Manage all registered users</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">← Admin Panel</a>
</div>

{{-- Search --}}
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('admin.users') }}">
            <div style="display:flex;gap:12px;align-items:center;">
                <div style="flex:1;">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="🔍 Search by name or email...">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.users') }}" class="btn btn-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-icon">👥</div>
        <div><h3>All Users</h3><p>{{ $users->total() }} registered</p></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Emails Sent</th>
                    <th>Campaigns</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:var(--gold-dim);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--gold);flex-shrink:0;">
                                {{ strtoupper(substr($u->name,0,1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13.5px;">{{ $u->name }}</div>
                                <div style="font-size:12px;color:var(--muted);">{{ $u->email }}</div>
                                @if($u->company)
                                <div style="font-size:11px;color:var(--muted);">{{ $u->company }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="{{ $u->role === 'admin' ? 'background:var(--gold-dim);color:var(--gold)' : 'background:#f3f4f6;color:#6b7280' }}">
                            {{ ucfirst($u->role ?? 'user') }}
                        </span>
                    </td>
                    <td style="font-family:'Syne',sans-serif;font-weight:700;">{{ number_format($u->sent_count) }}</td>
                    <td style="font-family:'Syne',sans-serif;font-weight:700;">{{ number_format($u->campaigns_count) }}</td>
                    <td style="font-size:12px;color:var(--muted);">{{ $u->created_at->format('M j, Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <button onclick="openEditUser({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ $u->email }}', '{{ $u->role ?? 'user' }}')"
                                class="btn btn-secondary btn-sm">✏️ Edit</button>
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.impersonate', $u) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" title="Log in as this user">👤 View As</button>
                            </form>
                            <form method="POST" action="{{ route('admin.users.delete', $u) }}"
                                onsubmit="return confirm('Delete {{ $u->name }} and ALL their data?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <span style="font-size:13px;color:var(--muted);">{{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</span>
            <div style="display:flex;gap:4px;">
                @if($users->onFirstPage())
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">← Prev</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Prev</a>
                @endif
                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                @else
                    <span class="btn btn-secondary btn-sm" style="opacity:.4;cursor:default;">Next →</span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Edit User Modal --}}
<div id="editUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:440px;">
        <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;margin-bottom:20px;">✏️ Edit User</h3>
        <form method="POST" id="editUserForm">
            @csrf @method('PATCH')
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" id="editUserName">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="editUserEmail">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="editUserRole" style="width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;background:#fff;outline:none;">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>New Password <span style="color:var(--muted);font-weight:400;font-size:12px;">(leave blank to keep)</span></label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="document.getElementById('editUserModal').style.display='none'" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditUser(id, name, email, role) {
    document.getElementById('editUserForm').action = '/admin/users/' + id;
    document.getElementById('editUserName').value  = name;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserRole').value  = role;
    document.getElementById('editUserModal').style.display = 'flex';
}
document.getElementById('editUserModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
@endpush
