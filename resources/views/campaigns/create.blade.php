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

            {{-- Template picker --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">🎨</div>
                    <div><h3>Email Content</h3><p>Design your email visually or edit raw HTML</p></div>
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
                        <p class="hint">Load a template as a starting point, then edit it visually below.</p>
                    </div>

                    {{-- Editor mode tabs --}}
                    <div style="display:flex;gap:0;margin-bottom:12px;border:1.5px solid var(--border);border-radius:8px;overflow:hidden;">
                        <button type="button" id="tab-visual"
                            onclick="switchTab('visual')"
                            style="flex:1;padding:10px;border:none;background:var(--ink);color:#fff;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;">
                            ✏️ Visual Editor
                        </button>
                        <button type="button" id="tab-html"
                            onclick="switchTab('html')"
                            style="flex:1;padding:10px;border:none;background:var(--cream);color:var(--muted);font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;">
                            &lt;/&gt; Raw HTML
                        </button>
                    </div>

                    {{-- Visual Editor (TinyMCE) --}}
                    <div id="editor-visual" class="form-group">
                        <textarea id="tinymce_editor" style="display:none;">{{ old('html_content', $campaign->html_content ?? '') }}</textarea>
                    </div>

                    {{-- Raw HTML Editor --}}
                    <div id="editor-html" class="form-group" style="display:none;">
                        <label>Raw HTML <span class="req">*</span></label>
                        <textarea id="html_content" rows="18"
                            placeholder="Paste your full HTML email here..."
                            class="{{ $errors->has('html_content') ? 'invalid' : '' }}"
                            style="font-family:'Courier New',monospace;font-size:12px;line-height:1.5;">{{ old('html_content', $campaign->html_content ?? '') }}</textarea>
                        @error('html_content') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Hidden field that actually gets submitted --}}
                    <input type="hidden" name="html_content" id="html_content_final"
                        value="{{ old('html_content', $campaign->html_content ?? '') }}">

                    <p class="hint" style="margin-top:8px;">
                        Variables (optional):
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
{{-- TinyMCE free CDN (no API key needed for basic use) --}}
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
let editorMode = 'visual';
let tinyReady  = false;

// ── Init TinyMCE ──────────────────────────────────────────────
tinymce.init({
    selector: '#tinymce_editor',
    height: 480,
    menubar: true,
    plugins: [
        'anchor', 'autolink', 'charmap', 'codesample', 'emoticons',
        'image', 'link', 'lists', 'media', 'searchreplace', 'table',
        'visualblocks', 'wordcount', 'code', 'fullscreen', 'preview',
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | ' +
             'bold italic underline strikethrough forecolor backcolor | ' +
             'link image table | ' +
             'alignleft aligncenter alignright alignjustify | ' +
             'bullist numlist outdent indent | ' +
             'removeformat | code fullscreen preview',
    skin: 'oxide',
    content_css: 'default',
    branding: false,
    promotion: false,
    setup: function(editor) {
        editor.on('init', function() {
            tinyReady = true;
            // Load existing content
            const existing = document.getElementById('html_content_final').value;
            if (existing) {
                editor.setContent(existing);
            }
            updatePreview();
        });
        editor.on('input change keyup', function() {
            syncFromVisual();
            updatePreview();
        });
    },
});

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

        // Sync raw HTML → visual editor
        if (tinyReady) {
            const raw = document.getElementById('html_content').value;
            tinymce.get('tinymce_editor').setContent(raw);
        }
    } else {
        htmlBtn.style.background   = 'var(--ink)';
        htmlBtn.style.color        = '#fff';
        visualBtn.style.background = 'var(--cream)';
        visualBtn.style.color      = 'var(--muted)';
        htmlDiv.style.display      = 'block';
        visualDiv.style.display    = 'none';

        // Sync visual editor → raw HTML textarea
        if (tinyReady) {
            document.getElementById('html_content').value =
                tinymce.get('tinymce_editor').getContent();
        }
        updatePreview();
    }
}

// ── Sync visual → hidden field ────────────────────────────────
function syncFromVisual() {
    if (tinyReady) {
        const content = tinymce.get('tinymce_editor').getContent();
        document.getElementById('html_content_final').value = content;
        document.getElementById('html_content').value = content;
    }
}

// ── Sync raw HTML → hidden field ─────────────────────────────
document.addEventListener('input', function(e) {
    if (e.target.id === 'html_content') {
        document.getElementById('html_content_final').value = e.target.value;
        updatePreview();
    }
});

// ── Before form submit — sync whichever editor is active ──────
document.getElementById('campaignForm').addEventListener('submit', function() {
    if (editorMode === 'visual' && tinyReady) {
        const content = tinymce.get('tinymce_editor').getContent();
        document.getElementById('html_content_final').value = content;
        document.getElementById('html_content').value = content;
    } else {
        document.getElementById('html_content_final').value =
            document.getElementById('html_content').value;
    }
});

// ── Template loader ────────────────────────────────────────────
const templates = {};
document.querySelectorAll('#templatePicker option[data-html]').forEach(opt => {
    templates[opt.value] = {
        html: opt.getAttribute('data-html'),
        subject: opt.getAttribute('data-subject'),
    };
});

function loadTemplate(id) {
    if (!id || !templates[id]) return;

    const decoder = document.createElement('textarea');
    decoder.innerHTML = templates[id].html;
    const html = decoder.value;

    // Load into both editors
    document.getElementById('html_content').value = html;
    document.getElementById('html_content_final').value = html;
    if (tinyReady) {
        tinymce.get('tinymce_editor').setContent(html);
    }

    document.getElementById('templateIdInput').value = id;

    // Only set subject if empty
    const subj = document.querySelector('input[name="subject"]');
    if (!subj.value) {
        const decoder2 = document.createElement('textarea');
        decoder2.innerHTML = templates[id].subject;
        subj.value = decoder2.value;
    }

    updatePreview();
}

// ── Live preview ───────────────────────────────────────────────
function updatePreview() {
    const html = document.getElementById('html_content_final').value
              || document.getElementById('html_content').value;
    const frame = document.getElementById('previewFrame');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(html); doc.close();
}

// ── Contact list validation ────────────────────────────────────
function validateAndConfirm() {
    const selected = document.querySelector('input[name="contact_list_id"]:checked');
    if (!selected) {
        alert('⚠️ Please select a contact list before sending.\n\nGo to Contacts → Create a list and add contacts first, then come back here.');
        return false;
    }
    return confirm('Send this campaign NOW to all active subscribers in the selected list?');
}

document.addEventListener('DOMContentLoaded', () => {
    updatePreview();
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