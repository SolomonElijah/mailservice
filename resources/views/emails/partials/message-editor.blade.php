{{--
    Reusable message editor with Visual / HTML tabs
    Params: $form (string: single|multiple|advanced), $hasText (bool, optional)
--}}
<div style="border:1.5px solid var(--border);border-radius:8px;overflow:hidden;">
    {{-- Tab bar --}}
    <div style="display:flex;background:var(--cream);border-bottom:1px solid var(--border);">
        <button type="button" id="{{ $form }}-tab-visual"
            onclick="switchMsgTab('{{ $form }}','visual')"
            style="flex:1;padding:9px;border:none;background:var(--ink);color:#fff;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;">
            ✏️ Visual
        </button>
        <button type="button" id="{{ $form }}-tab-html"
            onclick="switchMsgTab('{{ $form }}','html')"
            style="flex:1;padding:9px;border:none;background:var(--cream);color:var(--muted);font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;">
            &lt;/&gt; HTML
        </button>
        @if(isset($hasText) && $hasText)
        <button type="button" id="{{ $form }}-tab-text"
            onclick="switchMsgTab('{{ $form }}','text')"
            style="flex:1;padding:9px;border:none;background:var(--cream);color:var(--muted);font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;">
            Aa Plain Text
        </button>
        @endif
    </div>

    {{-- Visual editor (contenteditable) --}}
    <div id="{{ $form }}-editor-visual"
        contenteditable="true"
        oninput="syncVisualToHtml('{{ $form }}')"
        style="min-height:200px;padding:16px 18px;background:#fff;outline:none;font-size:14px;line-height:1.8;color:#333;font-family:'DM Sans',inherit;">
    </div>

    {{-- HTML editor --}}
    <div id="{{ $form }}-editor-html" style="display:none;">
        <textarea id="{{ $form }}-html-area" rows="12"
            oninput="syncHtmlToHidden('{{ $form }}')"
            placeholder="<p>Your HTML email content...</p>"
            style="width:100%;border:none;padding:14px;font-family:'Courier New',monospace;font-size:12px;line-height:1.5;resize:vertical;outline:none;background:#fafafa;">{{ old('html_body') }}</textarea>
    </div>

    {{-- Plain text (optional) --}}
    @if(isset($hasText) && $hasText)
    <div id="{{ $form }}-editor-text" style="display:none;">
        <textarea name="plain_body" rows="8"
            placeholder="Plain text fallback for email clients that don't support HTML..."
            style="width:100%;border:none;padding:14px;font-family:inherit;font-size:14px;line-height:1.7;resize:vertical;outline:none;">{{ old('plain_body') }}</textarea>
    </div>
    @endif
</div>

{{-- Hidden field that gets submitted --}}
<input type="hidden" name="html_body" id="{{ $form }}-hidden-html" value="{{ old('html_body') }}">
