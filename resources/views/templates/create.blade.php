@extends('layouts.app')
@section('title', isset($template) ? 'Edit Template' : 'New Template')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">{{ isset($template) ? 'Edit Template ✏️' : 'New Template 🎨' }}</h1>
        <p class="page-subtitle">{{ isset($template) ? 'Update your email template' : 'Create a reusable email template' }}</p>
    </div>
    <a href="{{ route('templates.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">

    {{-- Form --}}
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📝</div>
            <div><h3>Template Details</h3><p>Fill in all fields below</p></div>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}"
                id="templateForm">
                @csrf
                @if(isset($template)) @method('PUT') @endif

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="form-group">
                        <label>Template Name <span class="req">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}"
                            placeholder="e.g. Summer Sale 2025"
                            class="{{ $errors->has('name') ? 'invalid' : '' }}">
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Category <span class="req">*</span></label>
                        <select name="category" class="{{ $errors->has('category') ? 'invalid' : '' }}">
                            @foreach($categories as $key => $cat)
                                <option value="{{ $key }}" {{ old('category', $template->category ?? '') === $key ? 'selected' : '' }}>
                                    {{ $cat['icon'] }} {{ $cat['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('category') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Default Subject <span class="req">*</span></label>
                    <input type="text" name="subject"
                        value="{{ old('subject', $template->subject ?? '') }}"
                        placeholder="Use @{{first_name}}, @{{company}} as variables"
                        class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                    @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label>HTML Content <span class="req">*</span></label>
                    <textarea name="html_content" id="html_content" rows="16"
                        placeholder="Paste your full HTML email here..."
                        oninput="updatePreview()"
                        class="{{ $errors->has('html_content') ? 'invalid' : '' }}"
                        style="font-family:'Courier New',monospace;font-size:12px;line-height:1.6;">{{ old('html_content', $template->html_content ?? '') }}</textarea>
                    @error('html_content') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="hint">Supports full HTML. Use variables:
                        <code style="background:#f0ede8;padding:2px 5px;border-radius:3px;">@{{name}}</code>
                        <code style="background:#f0ede8;padding:2px 5px;border-radius:3px;">@{{first_name}}</code>
                        <code style="background:#f0ede8;padding:2px 5px;border-radius:3px;">@{{email}}</code>
                        <code style="background:#f0ede8;padding:2px 5px;border-radius:3px;">@{{company}}</code>
                    </p>
                </div>

                <div class="form-group">
                    <label>Plain Text (optional)</label>
                    <textarea name="plain_content" rows="4"
                        placeholder="Plain text fallback for email clients that don't support HTML...">{{ old('plain_content', $template->plain_content ?? '') }}</textarea>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        {{ isset($template) ? '💾 Update Template' : '✅ Save Template' }}
                    </button>
                    <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Live Preview --}}
    <div style="position:sticky;top:80px;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">👁</div>
                <div><h3>Live Preview</h3><p>Updates as you type</p></div>
                <div class="card-header-action">
                    <button onclick="refreshPreview()" class="btn btn-secondary btn-sm">🔄 Refresh</button>
                </div>
            </div>
            <div style="height:520px;overflow:hidden;border-bottom-left-radius:12px;border-bottom-right-radius:12px;">
                <iframe id="previewFrame" style="width:100%;height:100%;border:none;background:#f5f5f5;"></iframe>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function updatePreview() {
    const html = document.getElementById('html_content').value;
    const frame = document.getElementById('previewFrame');
    const doc = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
}
function refreshPreview() { updatePreview(); }
// Init on load
document.addEventListener('DOMContentLoaded', updatePreview);
</script>
@endpush