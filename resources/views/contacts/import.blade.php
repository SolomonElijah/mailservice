@extends('layouts.app')
@section('title', 'Import Contacts')

@section('content')

<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Import Contacts 📥</h1>
        <p class="page-subtitle">Bulk import contacts into <strong>{{ $list->name }}</strong> via CSV</p>
    </div>
    <a href="{{ route('contacts.show', $list) }}" class="btn btn-secondary">← Back to List</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    {{-- Upload Form --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📥</div>
            <div><h3>Upload CSV File</h3><p>Max 5MB · CSV format only</p></div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('contacts.import', $list) }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label>CSV File <span class="req">*</span></label>
                    <div id="dropZone" style="border:2px dashed var(--border);border-radius:10px;padding:40px 20px;text-align:center;cursor:pointer;transition:all .2s;background:var(--cream);"
                        onclick="document.getElementById('csv_file').click()"
                        ondragover="event.preventDefault();this.style.borderColor='var(--gold)';this.style.background='var(--gold-dim)'"
                        ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--cream)'"
                        ondrop="handleDrop(event)">
                        <div style="font-size:36px;margin-bottom:12px;">📂</div>
                        <p style="font-weight:600;color:var(--ink);margin-bottom:4px;">Drop CSV file here</p>
                        <p style="font-size:13px;color:var(--muted);">or click to browse</p>
                        <p id="fileName" style="font-size:12px;color:var(--gold);margin-top:10px;font-weight:600;"></p>
                    </div>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt"
                        style="display:none;" onchange="showFileName(this)">
                    @error('csv_file') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div style="background:var(--green-bg);border:1px solid #b2dfc4;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--green);">
                    ✅ <strong>Duplicate protection:</strong> Contacts already in this list will be automatically skipped.
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    📥 Import Contacts
                </button>
            </form>
        </div>
    </div>

    {{-- Instructions --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        <div class="card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <div><h3>CSV Requirements</h3><p>Format your file correctly</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:14px;line-height:1.6;">
                    Your CSV must include a <strong>header row</strong> as the first line. Supported column names:
                </p>

                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px;">
                    @foreach([
                        ['email',   'Required', 'var(--red)',  'Recipient email address'],
                        ['name',    'Optional', 'var(--gold)', 'Full name (or use first_name)'],
                        ['company', 'Optional', 'var(--muted)','Company or organisation'],
                    ] as [$col, $req, $color, $desc])
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--cream);border-radius:8px;">
                        <code style="background:var(--ink);color:var(--gold);padding:3px 8px;border-radius:4px;font-size:12px;">{{ $col }}</code>
                        <span style="font-size:11px;font-weight:700;color:{{ $color }};text-transform:uppercase;letter-spacing:.5px;">{{ $req }}</span>
                        <span style="font-size:13px;color:var(--muted);">{{ $desc }}</span>
                    </div>
                    @endforeach
                </div>

                <p style="font-size:13px;font-weight:600;color:var(--ink);margin-bottom:8px;">Example CSV:</p>
                <code style="display:block;background:var(--ink);color:var(--gold);padding:14px 16px;border-radius:8px;font-size:12px;line-height:2;font-family:'Courier New',monospace;overflow-x:auto;white-space:pre;">email,name,company
alice@example.com,Alice Johnson,Acme Corp
bob@example.com,Bob Smith,
charlie@example.com,,TechCorp</code>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-icon">⬇️</div>
                <div><h3>Download Template</h3><p>Start with our sample CSV</p></div>
            </div>
            <div class="card-body">
                <p style="font-size:14px;color:var(--muted);margin-bottom:16px;line-height:1.6;">
                    Download our pre-formatted CSV template to fill in your contacts.
                </p>
                <a href="{{ route('contacts.template.download') }}" class="btn btn-secondary" style="width:100%;justify-content:center;">
                    ⬇️ Download CSV Template
                </a>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
function showFileName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('fileName').textContent = '✅ ' + input.files[0].name;
        document.getElementById('dropZone').style.borderColor = 'var(--gold)';
    }
}
function handleDrop(e) {
    e.preventDefault();
    const zone = document.getElementById('dropZone');
    zone.style.borderColor = 'var(--border)';
    zone.style.background = 'var(--cream)';
    const files = e.dataTransfer.files;
    if (files.length) {
        document.getElementById('csv_file').files = files;
        document.getElementById('fileName').textContent = '✅ ' + files[0].name;
        zone.style.borderColor = 'var(--gold)';
    }
}
</script>
@endpush
