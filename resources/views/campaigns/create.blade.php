@extends('layouts.app')
@section('title', isset($campaign) ? 'Edit Campaign' : 'New Campaign')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">{{ isset($campaign) ? 'Edit Campaign ✏️' : 'New Campaign 📣' }}</h1>
        <p class="page-subtitle">{{ isset($campaign) ? 'Update your campaign details' : 'Set up and launch your email campaign' }}</p>
    </div>
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">← Back</a>
</div>

<form method="POST"
    action="{{ isset($campaign) ? route('campaigns.update', $campaign) : route('campaigns.store') }}"
    id="campaignForm">
    @csrf
    @if(isset($campaign)) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start;">

        {{-- Left: Main form --}}
        <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Basic Info --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">📝</div>
                    <div><h3>Campaign Details</h3><p>Name, type and content</p></div>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label>Campaign Name <span class="req">*</span></label>
                            <input type="text" name="name"
                                value="{{ old('name', $campaign->name ?? '') }}"
                                placeholder="e.g. January Newsletter"
                                class="{{ $errors->has('name') ? 'invalid' : '' }}">
                            @error('name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label>Campaign Type <span class="req">*</span></label>
                            <select name="type" class="{{ $errors->has('type') ? 'invalid' : '' }}">
                                @foreach($types as $key => $t)
                                <option value="{{ $key }}" {{ old('type', $campaign->type ?? '') === $key ? 'selected' : '' }}>
                                    {{ $t['icon'] }} {{ $t['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Subject <span class="req">*</span></label>
                        <input type="text" name="subject"
                            value="{{ old('subject', $campaign->subject ?? '') }}"
                            placeholder="Compelling subject line..."
                            class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                        @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Template picker + Editor --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">🎨</div>
                    <div><h3>Email Content</h3><p>Load a template then click any text to edit it</p></div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Load from Template</label>
                        <select id="templatePicker" onchange="loadTemplate(this.value)"
                            style="padding:11px 14px;background:#fff;border:1.5px solid var(--border);border-radius:8px;width:100%;font-family:inherit;font-size:14px;outline:none;cursor:pointer;">
                            <option value="">— Select a template to load —</option>
                            @foreach($templates as $t)
                            <option value="{{ $t->id }}"
                                data-html="{{ $t->html_content }}"
                                data-subject="{{ $t->subject }}"
                                {{ old('email_template_id', $campaign->email_template_id ?? '') == $t->id ? 'selected' : '' }}>
                                {{ $t->category_icon }} {{ $t->name }}
                            </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="email_template_id" id="templateIdInput"
                            value="{{ old('email_template_id', $campaign->email_template_id ?? '') }}">
                    </div>

                    {{-- Editor mode tabs --}}
                    <div style="display:flex;gap:0;margin-bottom:0;border:1.5px solid var(--border);border-radius:8px 8px 0 0;overflow:hidden;">
                        <button type="button" id="tab-visual" onclick="switchTab('visual')"
                            style="flex:1;padding:10px;border:none;background:var(--ink);color:#fff;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;">
                            🖱️ Click-to-Edit Preview
                        </button>
                        <button type="button" id="tab-html" onclick="switchTab('html')"
                            style="flex:1;padding:10px;border:none;background:var(--cream);color:var(--muted);font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;">
                            &lt;/&gt; Raw HTML
                        </button>
                    </div>

                    {{-- Visual: iframe with contenteditable injected --}}
                    <div id="editor-visual"
                        style="border:1.5px solid var(--border);border-top:none;border-radius:0 0 8px 8px;overflow:hidden;background:#f5f5f5;">
                        <div style="background:#fef9e8;border-bottom:1px solid #fde68a;padding:8px 14px;font-size:12px;color:#92400e;display:flex;align-items:center;gap:6px;">
                            ✏️ <strong>Click any text</strong> in the preview below to edit it directly. Styles are fully preserved.
                        </div>
                        <iframe id="editableFrame"
                            style="width:100%;height:520px;border:none;background:#fff;display:block;">
                        </iframe>
                    </div>

                    {{-- Raw HTML textarea --}}
                    <div id="editor-html" style="display:none;">
                        <textarea id="html_content" rows="20"
                            placeholder="Paste your full HTML email here..."
                            class="{{ $errors->has('html_content') ? 'invalid' : '' }}"
                            style="font-family:'Courier New',monospace;font-size:12px;line-height:1.5;border-radius:0 0 8px 8px;border-top:none;width:100%;resize:vertical;">{{ old('html_content', $campaign->html_content ?? '') }}</textarea>
                        @error('html_content') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Hidden field submitted with form --}}
                    <input type="hidden" name="html_content" id="html_content_final"
                        value="{{ old('html_content', $campaign->html_content ?? '') }}">

                    <p class="hint" style="margin-top:10px;">
                        Optional personalisation variables:
                        <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{name}}</code>
                        <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{first_name}}</code>
                        <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{email}}</code>
                        <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{company}}</code>
                    </p>
                </div>
            </div>

            {{-- Sender + Schedule --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">⚙️</div>
                    <div><h3>Sender & Schedule</h3><p>From details and optional send time</p></div>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label>From Name</label>
                            <input type="text" name="from_name"
                                value="{{ old('from_name', $campaign->from_name ?? config('app.name')) }}"
                                placeholder="{{ config('app.name') }}">
                        </div>
                        <div class="form-group">
                            <label>From Email</label>
                            <input type="email" name="from_email"
                                value="{{ old('from_email', $campaign->from_email ?? config('mail.from.address')) }}"
                                placeholder="{{ config('mail.from.address') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Schedule Send (optional)</label>
                        <input type="datetime-local" name="scheduled_at"
                            value="{{ old('scheduled_at', isset($campaign->scheduled_at) ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                        <p class="hint">Leave blank to save as draft. Note: auto-scheduling requires a queue worker.</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right: Contact list + preview --}}
        <div style="display:flex;flex-direction:column;gap:20px;position:sticky;top:72px;">

            {{-- Contact List --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">👥</div>
                    <div><h3>Contact List</h3><p>Who receives this campaign</p></div>
                </div>
                <div class="card-body">
                    @forelse($contactLists as $list)
                    <label style="display:flex;align-items:center;gap:10px;padding:10px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;margin-bottom:8px;transition:border-color .15s;"
                        onclick="this.style.borderColor='var(--gold)'">
                        <input type="radio" name="contact_list_id" value="{{ $list->id }}"
                            style="accent-color:var(--gold);width:16px;height:16px;"
                            {{ old('contact_list_id', $campaign->contact_list_id ?? '') == $list->id ? 'checked' : '' }}>
                        <div>
                            <div style="font-weight:600;font-size:13.5px;">{{ $list->name }}</div>
                            <div style="font-size:12px;color:var(--muted);">{{ $list->contacts_count }} contacts</div>
                        </div>
                    </label>
                    @empty
                    <div style="text-align:center;padding:20px;color:var(--muted);">
                        <p style="font-size:13px;margin-bottom:10px;">No contact lists yet.</p>
                        <a href="{{ route('contacts.index') }}" class="btn btn-secondary btn-sm">+ Create List</a>
                    </div>
                    @endforelse
                    @error('contact_list_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">👁</div>
                    <div><h3>Preview</h3></div>
                    <div class="card-header-action">
                        <button type="button" onclick="updatePreview()" class="btn btn-secondary btn-sm">🔄</button>
                    </div>
                </div>
                <div style="height:300px;overflow:hidden;border-bottom-left-radius:12px;border-bottom-right-radius:12px;">
                    <iframe id="previewFrame" style="width:143%;height:143%;border:none;background:#fff;transform:scale(0.7);transform-origin:top left;"></iframe>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="card">
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                    <button type="submit" name="action" value="save" class="btn btn-secondary" style="justify-content:center;">
                        💾 Save as Draft
                    </button>
                    <button type="submit" name="send_now" value="1" class="btn btn-primary" style="justify-content:center;"
                        onclick="return validateAndConfirm()">
                        🚀 Save & Send Now
                    </button>
                    <p style="font-size:11px;color:var(--muted);text-align:center;">Sending immediately will deliver to all active subscribers in the selected list.</p>
                </div>
            </div>

        </div>

    </div>
</form>
@endsection

@push('scripts')
<script>
let editorMode = 'visual';

// ── Write HTML into the editable iframe ──────────────────────
function renderEditableFrame(html) {
    const frame = document.getElementById('editableFrame');
    const doc   = frame.contentDocument || frame.contentWindow.document;

    // Inject the HTML + make every text node editable + sync back on change
    const wrapped = html.replace('</body>',
        `<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make all text-bearing elements click-to-edit
            document.querySelectorAll('p,h1,h2,h3,h4,h5,h6,span,a,td,th,li,div,strong,em,b,i').forEach(function(el) {
                if (el.children.length === 0 && el.textContent.trim()) {
                    el.setAttribute('contenteditable', 'true');
                    el.style.outline = 'none';
                    el.style.cursor  = 'text';
                    el.addEventListener('focus', function() {
                        this.style.outline = '2px dashed #d4a843';
                        this.style.outlineOffset = '2px';
                        this.style.borderRadius = '2px';
                    });
                    el.addEventListener('blur', function() {
                        this.style.outline = 'none';
                        parent.syncFromFrame();
                    });
                    el.addEventListener('keyup', function() {
                        parent.syncFromFrame();
                    });
                }
            });
        });
        <\/script></body>`
    );

    doc.open();
    doc.write(wrapped);
    doc.close();
}

// ── Pull HTML back from iframe → hidden field + raw textarea ──
window.syncFromFrame = function() {
    const frame = document.getElementById('editableFrame');
    const doc   = frame.contentDocument || frame.contentWindow.document;
    const html  = doc.documentElement.outerHTML;
    document.getElementById('html_content_final').value = html;
    document.getElementById('html_content').value = html;

    // Also update the small right-panel preview
    updatePreview();
};

// ── Tab switching ─────────────────────────────────────────────
function switchTab(mode) {
    editorMode = mode;
    const visualBtn = document.getElementById('tab-visual');
    const htmlBtn   = document.getElementById('tab-html');
    const visualDiv = document.getElementById('editor-visual');
    const htmlDiv   = document.getElementById('editor-html');

    if (mode === 'visual') {
        visualBtn.style.background = 'var(--ink)';
        visualBtn.style.color      = '#fff';
        htmlBtn.style.background   = 'var(--cream)';
        htmlBtn.style.color        = 'var(--muted)';
        visualDiv.style.display    = 'block';
        htmlDiv.style.display      = 'none';
        // Re-render iframe with current raw HTML
        renderEditableFrame(document.getElementById('html_content').value);
    } else {
        htmlBtn.style.background   = 'var(--ink)';
        htmlBtn.style.color        = '#fff';
        visualBtn.style.background = 'var(--cream)';
        visualBtn.style.color      = 'var(--muted)';
        htmlDiv.style.display      = 'block';
        visualDiv.style.display    = 'none';
        updatePreview();
    }
}

// ── Template loader ───────────────────────────────────────────
const templates = {};
document.querySelectorAll('#templatePicker option[data-html]').forEach(opt => {
    templates[opt.value] = {
        html:    opt.getAttribute('data-html'),
        subject: opt.getAttribute('data-subject'),
    };
});

function loadTemplate(id) {
    if (!id || !templates[id]) return;

    const decoder = document.createElement('textarea');
    decoder.innerHTML = templates[id].html;
    const html = decoder.value;

    document.getElementById('html_content').value       = html;
    document.getElementById('html_content_final').value = html;
    document.getElementById('templateIdInput').value    = id;

    // Fill subject only if currently empty
    const subj = document.querySelector('input[name="subject"]');
    if (!subj.value) {
        const d = document.createElement('textarea');
        d.innerHTML = templates[id].subject;
        subj.value = d.value;
    }

    // Render into editable frame if in visual mode
    if (editorMode === 'visual') {
        renderEditableFrame(html);
    }
    updatePreview();
}

// ── Sync raw textarea → hidden field + re-render frame ────────
document.addEventListener('input', function(e) {
    if (e.target.id === 'html_content') {
        document.getElementById('html_content_final').value = e.target.value;
        updatePreview();
    }
});

// ── Right-panel preview ───────────────────────────────────────
function updatePreview() {
    const html = document.getElementById('html_content_final').value
              || document.getElementById('html_content').value;
    const frame = document.getElementById('previewFrame');
    if (!frame) return;
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(html); doc.close();
}

// ── Before submit — make sure hidden field is up to date ──────
document.getElementById('campaignForm').addEventListener('submit', function() {
    if (editorMode === 'visual') {
        window.syncFromFrame();
    } else {
        document.getElementById('html_content_final').value =
            document.getElementById('html_content').value;
    }
});

// ── Contact list validation ───────────────────────────────────
function validateAndConfirm() {
    const selected = document.querySelector('input[name="contact_list_id"]:checked');
    if (!selected) {
        alert('⚠️ Please select a contact list before sending.\n\nGo to Contacts → Create a list and add contacts first.');
        return false;
    }
    return confirm('Send this campaign NOW to all active subscribers in the selected list?');
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const existing = document.getElementById('html_content_final').value;
    if (existing) {
        renderEditableFrame(existing);
        updatePreview();
    }

    document.querySelectorAll('input[name="contact_list_id"]').forEach(r => {
        if (r.checked) r.closest('label').style.borderColor = 'var(--gold)';
        r.addEventListener('change', function() {
            document.querySelectorAll('input[name="contact_list_id"]').forEach(x => {
                x.closest('label').style.borderColor = 'var(--border)';
            });
            if (this.checked) this.closest('label').style.borderColor = 'var(--gold)';
        });
    });
});
</script>
@endpush
