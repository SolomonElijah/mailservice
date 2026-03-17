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
                    <div><h3>Email Content</h3><p>Choose a template or write custom HTML</p></div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Load from Template</label>
                        <select id="templatePicker" onchange="loadTemplate(this.value)"
                            style="padding:11px 14px;background:#fff;border:1.5px solid var(--border);border-radius:8px;width:100%;font-family:inherit;font-size:14px;outline:none;cursor:pointer;">
                            <option value="">— Select a template to load —</option>
                            @foreach($templates as $t)
                            <option value="{{ $t->id }}"
                                data-html="{{ htmlspecialchars($t->html_content) }}"
                                data-subject="{{ htmlspecialchars($t->subject) }}"
                                {{ old('email_template_id', $campaign->email_template_id ?? '') == $t->id ? 'selected' : '' }}>
                                {{ $t->category_icon }} {{ $t->name }}
                            </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="email_template_id" id="templateIdInput"
                            value="{{ old('email_template_id', $campaign->email_template_id ?? '') }}">
                        <p class="hint">Selecting a template will fill the HTML editor below. You can still edit it.</p>
                    </div>

                    <div class="form-group">
                        <label>HTML Content <span class="req">*</span></label>
                        <textarea name="html_content" id="html_content" rows="14"
                            placeholder="Full HTML email content..."
                            oninput="updatePreview()"
                            class="{{ $errors->has('html_content') ? 'invalid' : '' }}"
                            style="font-family:'Courier New',monospace;font-size:12px;line-height:1.5;">{{ old('html_content', $campaign->html_content ?? '') }}</textarea>
                        @error('html_content') <p class="field-error">{{ $message }}</p> @enderror
                        <p class="hint">Variables: <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">{{name}}</code> <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">{{first_name}}</code> <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">{{email}}</code> <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">{{company}}</code></p>
                    </div>
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
                    <iframe id="previewFrame" style="width:100%;height:100%;border:none;background:#f5f5f5;transform:scale(0.7);transform-origin:top left;width:143%;height:143%;"></iframe>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="card">
                <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                    <button type="submit" name="action" value="save" class="btn btn-secondary" style="justify-content:center;">
                        💾 Save as Draft
                    </button>
                    <button type="submit" name="send_now" value="1" class="btn btn-primary" style="justify-content:center;"
                        onclick="return confirm('Send this campaign NOW to all selected contacts?')">
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
// Template loader
const templates = {};
document.querySelectorAll('#templatePicker option[data-html]').forEach(opt => {
    templates[opt.value] = {
        html: opt.getAttribute('data-html'),
        subject: opt.getAttribute('data-subject'),
    };
});

function loadTemplate(id) {
    if (!id || !templates[id]) return;
    document.getElementById('html_content').value = templates[id].html;
    document.getElementById('templateIdInput').value = id;
    const subj = document.querySelector('input[name="subject"]');
    if (!subj.value) subj.value = templates[id].subject;
    updatePreview();
}

function updatePreview() {
    const html = document.getElementById('html_content').value;
    const frame = document.getElementById('previewFrame');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open(); doc.write(html); doc.close();
}

document.addEventListener('DOMContentLoaded', () => {
    updatePreview();
    // Highlight selected radio
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
