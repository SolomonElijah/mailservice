@extends('layouts.app')
@section('title', isset($template) ? 'Edit Template' : 'New Template')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">{{ isset($template) ? 'Edit Template ✏️' : 'New Template 🎨' }}</h1>
        <p class="page-subtitle">{{ isset($template) ? 'Update your email template' : 'Build a reusable email template' }}</p>
    </div>
    <a href="{{ route('templates.index') }}" class="btn btn-secondary">← Back</a>
</div>

<form method="POST"
    action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}"
    id="templateForm">
    @csrf
    @if(isset($template)) @method('PUT') @endif

    {{-- Top meta fields --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Template Name <span class="req">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}"
                        placeholder="e.g. Summer Sale 2025"
                        class="{{ $errors->has('name') ? 'invalid' : '' }}">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Category <span class="req">*</span></label>
                    <select name="category" class="{{ $errors->has('category') ? 'invalid' : '' }}">
                        @foreach($categories as $key => $cat)
                            <option value="{{ $key }}" {{ old('category', $template->category ?? '') === $key ? 'selected' : '' }}>
                                {{ $cat['icon'] }} {{ $cat['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Default Subject <span class="req">*</span></label>
                    <input type="text" name="subject"
                        value="{{ old('subject', $template->subject ?? '') }}"
                        placeholder="Your email subject..."
                        class="{{ $errors->has('subject') ? 'invalid' : '' }}">
                    @error('subject') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Main editor area --}}
    <div style="display:grid;grid-template-columns:200px 1fr 320px;gap:16px;align-items:start;">

        {{-- LEFT: Block palette --}}
        <div style="position:sticky;top:72px;" id="blockPalette">
            <div class="card">
                <div class="card-header" style="padding:14px 16px;">
                    <div><h3 style="font-size:13px;">📦 Blocks</h3><p style="font-size:11px;">Drag onto canvas</p></div>
                </div>
                <div style="padding:10px;">
                    @php
                    $blocks = [
                        ['logo',     '🏷️', 'Logo / Brand'],
                        ['hero',     '🖼️', 'Hero Banner'],
                        ['heading',  '🔤', 'Heading'],
                        ['text',     '📝', 'Paragraph'],
                        ['button',   '🔘', 'Button'],
                        ['image',    '🌄', 'Image'],
                        ['divider',  '➖', 'Divider'],
                        ['spacer',   '↕️', 'Spacer'],
                        ['two-col',  '⬛⬛','Two Columns'],
                        ['feature',  '⭐', 'Feature Row'],
                        ['social',   '🌐', 'Social Links'],
                        ['footer',   '📄', 'Footer'],
                    ];
                    @endphp
                    @foreach($blocks as [$type, $icon, $label])
                    <div class="block-item"
                        draggable="true"
                        data-block="{{ $type }}"
                        ondragstart="dragStart(event)"
                        style="display:flex;align-items:center;gap:8px;padding:8px 10px;margin-bottom:4px;background:var(--cream);border:1.5px solid var(--border);border-radius:7px;cursor:grab;font-size:12px;font-weight:600;user-select:none;transition:border-color .15s;">
                        <span style="font-size:15px;">{{ $icon }}</span>{{ $label }}
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- CENTRE: Canvas / tabs --}}
        <div>
            <div style="display:flex;gap:0;border:1.5px solid var(--border);border-radius:8px 8px 0 0;overflow:hidden;">
                <button type="button" id="tab-builder" onclick="switchTab('builder')"
                    style="flex:1;padding:10px;border:none;background:var(--ink);color:#fff;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;">
                    🧱 Builder
                </button>
                <button type="button" id="tab-visual" onclick="switchTab('visual')"
                    style="flex:1;padding:10px;border:none;background:var(--cream);color:var(--muted);font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;">
                    🖱️ Click-to-Edit
                </button>
                <button type="button" id="tab-html" onclick="switchTab('html')"
                    style="flex:1;padding:10px;border:none;background:var(--cream);color:var(--muted);font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;">
                    &lt;/&gt; Raw HTML
                </button>
            </div>

            <div id="editor-builder"
                style="border:1.5px solid var(--border);border-top:none;border-radius:0 0 8px 8px;min-height:600px;background:#e8e4de;padding:20px;">
                <div style="background:#fef9e8;border:1px dashed #fde68a;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:16px;text-align:center;">
                    ← Drag blocks from the left panel and drop them here
                </div>
                <div id="canvas"
                    ondragover="dragOver(event)"
                    ondrop="dropBlock(event)"
                    style="min-height:400px;background:#fff;border-radius:8px;max-width:600px;margin:0 auto;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);">
                </div>
            </div>

            <div id="editor-visual" style="display:none;border:1.5px solid var(--border);border-top:none;border-radius:0 0 8px 8px;overflow:hidden;">
                <div style="background:#fef9e8;border-bottom:1px solid #fde68a;padding:8px 14px;font-size:12px;color:#92400e;">
                    ✏️ <strong>Click any text</strong> to edit it directly. All styles are preserved.
                </div>
                <iframe id="editableFrame" style="width:100%;height:600px;border:none;background:#fff;display:block;"></iframe>
            </div>

            <div id="editor-html" style="display:none;">
                <textarea id="html_content" rows="24"
                    placeholder="Paste or write your full HTML email here..."
                    class="{{ $errors->has('html_content') ? 'invalid' : '' }}"
                    style="font-family:'Courier New',monospace;font-size:12px;line-height:1.5;border-radius:0 0 8px 8px;border:1.5px solid var(--border);border-top:none;width:100%;resize:vertical;padding:14px;">{{ old('html_content', $template->html_content ?? '') }}</textarea>
                @error('html_content') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <input type="hidden" name="html_content" id="html_content_final"
                value="{{ old('html_content', $template->html_content ?? '') }}">

            <p class="hint" style="margin-top:10px;">
                Optional variables:
                <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{name}}</code>
                <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{first_name}}</code>
                <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{email}}</code>
                <code style="background:#f0ede8;padding:1px 5px;border-radius:3px;font-size:11px;">@{{company}}</code>
            </p>

            <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    {{ isset($template) ? '💾 Update Template' : '✅ Save Template' }}
                </button>
                <a href="{{ route('templates.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="button" onclick="clearCanvas()" class="btn btn-secondary" id="clearBtn" style="margin-left:auto;">
                    🗑 Clear Canvas
                </button>
            </div>
        </div>

        {{-- RIGHT: Preview + plain text --}}
        <div style="position:sticky;top:72px;">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">👁</div>
                    <div><h3>Preview</h3><p>Live output</p></div>
                    <div class="card-header-action">
                        <button type="button" onclick="refreshPreview()" class="btn btn-secondary btn-sm">🔄</button>
                    </div>
                </div>
                <div style="height:520px;overflow:hidden;border-bottom-left-radius:12px;border-bottom-right-radius:12px;">
                    <iframe id="previewFrame" style="width:160%;height:160%;border:none;background:#fff;transform:scale(0.625);transform-origin:top left;"></iframe>
                </div>
            </div>
            <div class="card" style="margin-top:12px;">
                <div class="card-body" style="padding:14px 16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Plain Text (optional)</label>
                        <textarea name="plain_content" rows="3" placeholder="Plain text fallback..." style="font-size:13px;">{{ old('plain_content', $template->plain_content ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

{{-- Block editor modal --}}
<div id="blockEditorModal"
    style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;">
            <h3 id="modalTitle" style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;"></h3>
            <button type="button" onclick="document.getElementById('blockEditorModal').style.display='none'"
                style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted);">✕</button>
        </div>
        <div id="modalFields" style="padding:20px 24px;"></div>
        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;position:sticky;bottom:0;background:#fff;">
            <button type="button" id="modalSave" class="btn btn-primary">💾 Save Changes</button>
            <button type="button" onclick="document.getElementById('blockEditorModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────
let editorMode   = 'builder';
let canvasBlocks = [];
let dragBlockType = null;
let blockIdCounter = 0;

// ── Block defaults ────────────────────────────────────────────
const blockDefaults = {
    logo:     { brand:'YourBrand', brandColor:'#d4a843', bgColor:'#0d0d14' },
    hero:     { heading:'Your Compelling Headline', subtext:'Write a short description that gets your readers excited.', btnText:'Get Started →', btnUrl:'#', bgColor:'#ffffff' },
    heading:  { text:'Section Heading', level:'h2', align:'left', color:'#0d0d14' },
    text:     { text:'Write your paragraph here. Keep it concise and focused on value.', align:'left', color:'#444444' },
    button:   { text:'Click Here', url:'#', bgColor:'#0d0d14', textColor:'#ffffff', align:'center' },
    image:    { src:'https://via.placeholder.com/600x200/f0ede8/0d0d14?text=Your+Image', alt:'Image', link:'' },
    divider:  { color:'#e5e7eb', thickness:1 },
    spacer:   { height:24 },
    'two-col':{ left:'Left column content.', right:'Right column content.' },
    feature:  { icon:'🚀', title:'Feature Title', text:'Describe this feature in one or two sentences.' },
    social:   { links:[{name:'Twitter',url:'#',color:'#1da1f2'},{name:'LinkedIn',url:'#',color:'#0077b5'},{name:'Facebook',url:'#',color:'#1877f2'}] },
    footer:   { companyName:'Your Company', address:'123 Main Street, City', unsubUrl:'#' },
};

// ── Drag from palette ─────────────────────────────────────────
function dragStart(e) {
    dragBlockType = e.target.closest('[data-block]').dataset.block;
    e.dataTransfer.effectAllowed = 'copy';
}
function dragOver(e) {
    e.preventDefault();
    document.getElementById('canvas').style.boxShadow = '0 0 0 3px #d4a843';
}
function dropBlock(e) {
    e.preventDefault();
    document.getElementById('canvas').style.boxShadow = '0 4px 24px rgba(0,0,0,.1)';
    if (dragBlockType) { addBlock(dragBlockType); dragBlockType = null; }
}

// ── Add / remove / move blocks ────────────────────────────────
function addBlock(type, data = null) {
    const id = 'b' + (++blockIdCounter);
    canvasBlocks.push({ id, type, data: data || JSON.parse(JSON.stringify(blockDefaults[type] || {})) });
    renderCanvas();
    syncCanvasToHtml();
}
function removeBlock(id) {
    canvasBlocks = canvasBlocks.filter(b => b.id !== id);
    renderCanvas(); syncCanvasToHtml();
}
function moveBlock(id, dir) {
    const i = canvasBlocks.findIndex(b => b.id === id);
    if (dir === 'up'   && i > 0)                      [canvasBlocks[i-1], canvasBlocks[i]] = [canvasBlocks[i], canvasBlocks[i-1]];
    if (dir === 'down' && i < canvasBlocks.length - 1) [canvasBlocks[i], canvasBlocks[i+1]] = [canvasBlocks[i+1], canvasBlocks[i]];
    renderCanvas(); syncCanvasToHtml();
}
function clearCanvas() {
    if (canvasBlocks.length && !confirm('Clear all blocks?')) return;
    canvasBlocks = []; renderCanvas(); syncCanvasToHtml();
}

// ── Render canvas blocks ──────────────────────────────────────
function renderCanvas() {
    const canvas = document.getElementById('canvas');
    if (!canvasBlocks.length) {
        canvas.innerHTML = '<div style="text-align:center;padding:80px 20px;color:#aaa;font-size:14px;">← Drag blocks here to start building</div>';
        return;
    }
    canvas.innerHTML = canvasBlocks.map(b => renderBlockEditor(b)).join('');
}

function renderBlockEditor({id, type, data: d}) {
    const ctrl = `<div class="block-controls" style="position:absolute;top:4px;right:4px;display:none;gap:3px;z-index:10;background:rgba(255,255,255,.95);border-radius:6px;padding:3px;">
        <button type="button" onclick="moveBlock('${id}','up')" style="background:#f3f4f6;border:none;border-radius:4px;padding:3px 7px;cursor:pointer;font-size:11px;">↑</button>
        <button type="button" onclick="moveBlock('${id}','down')" style="background:#f3f4f6;border:none;border-radius:4px;padding:3px 7px;cursor:pointer;font-size:11px;">↓</button>
        <button type="button" onclick="openBlockEditor('${id}')" style="background:#d4a843;border:none;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:11px;color:#fff;font-weight:700;">Edit</button>
        <button type="button" onclick="removeBlock('${id}')" style="background:#e53e3e;border:none;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:11px;color:#fff;font-weight:700;">✕</button>
    </div>`;
    const inner = blockPreviewHtml(type, d);
    return `<div style="position:relative;border-bottom:1px solid #f0ede8;" onmouseenter="this.querySelector('.block-controls').style.display='flex'" onmouseleave="this.querySelector('.block-controls').style.display='none'">${ctrl}${inner}</div>`;
}

function blockPreviewHtml(type, d) {
    switch(type) {
        case 'logo':     return `<div style="background:${d.bgColor};padding:22px 40px;text-align:center;"><span style="font-size:20px;font-weight:800;color:#fff;font-family:sans-serif;">${d.brand.slice(0,-1)}<span style="color:${d.brandColor}">${d.brand.slice(-1)}</span></span></div>`;
        case 'hero':     return `<div style="background:${d.bgColor};padding:40px;text-align:center;"><h1 style="font-size:26px;font-weight:800;color:#0d0d14;margin-bottom:12px;">${d.heading}</h1><p style="font-size:14px;color:#555;margin-bottom:20px;line-height:1.6;">${d.subtext}</p><a style="display:inline-block;background:#0d0d14;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;font-size:14px;">${d.btnText}</a></div>`;
        case 'heading':  return `<div style="padding:14px 40px 8px;"><${d.level} style="font-size:${d.level==='h1'?'26':d.level==='h2'?'20':'16'}px;font-weight:800;color:${d.color};text-align:${d.align};margin:0;">${d.text}</${d.level}></div>`;
        case 'text':     return `<div style="padding:8px 40px 14px;"><p style="font-size:14px;color:${d.color};line-height:1.7;text-align:${d.align};margin:0;">${d.text}</p></div>`;
        case 'button':   return `<div style="padding:14px 40px;text-align:${d.align};"><span style="display:inline-block;background:${d.bgColor};color:${d.textColor};padding:12px 28px;border-radius:8px;font-weight:700;font-size:14px;">${d.text}</span></div>`;
        case 'image':    return `<img src="${d.src}" alt="${d.alt}" style="width:100%;display:block;">`;
        case 'divider':  return `<div style="padding:8px 40px;"><hr style="border:none;border-top:${d.thickness}px solid ${d.color};margin:0;"></div>`;
        case 'spacer':   return `<div style="height:${d.height}px;background:repeating-linear-gradient(45deg,#f9f9f9,#f9f9f9 4px,#fff 4px,#fff 12px);"></div>`;
        case 'two-col':  return `<div style="padding:14px 40px;display:grid;grid-template-columns:1fr 1fr;gap:16px;"><div style="font-size:13px;color:#555;border:1px dashed #ddd;padding:10px;border-radius:6px;">${d.left}</div><div style="font-size:13px;color:#555;border:1px dashed #ddd;padding:10px;border-radius:6px;">${d.right}</div></div>`;
        case 'feature':  return `<div style="padding:12px 40px;display:flex;gap:14px;align-items:flex-start;"><div style="width:40px;height:40px;min-width:40px;background:#d4a84320;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">${d.icon}</div><div><div style="font-weight:700;font-size:14px;color:#0d0d14;">${d.title}</div><div style="font-size:13px;color:#666;margin-top:3px;">${d.text}</div></div></div>`;
        case 'social':   return `<div style="padding:14px 40px;text-align:center;">${d.links.map(l=>`<span style="display:inline-block;margin:0 5px;background:${l.color};color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;">${l.name}</span>`).join('')}</div>`;
        case 'footer':   return `<div style="background:#0d0d14;padding:20px 40px;text-align:center;"><p style="color:rgba(255,255,255,.5);font-size:12px;margin-bottom:4px;">© 2025 ${d.companyName}</p><p style="color:rgba(255,255,255,.3);font-size:11px;">${d.address}</p></div>`;
        default:         return `<div style="padding:16px;color:#aaa;font-size:13px;">${type}</div>`;
    }
}

// ── Block editor modal ────────────────────────────────────────
function openBlockEditor(id) {
    const block = canvasBlocks.find(b => b.id === id);
    if (!block) return;
    document.getElementById('modalTitle').textContent = 'Edit ' + block.type.replace('-',' ').replace(/\b\w/g,c=>c.toUpperCase());
    document.getElementById('modalFields').innerHTML = buildEditorFields(block);
    document.getElementById('modalSave').onclick = () => saveBlockEdit(id);
    document.getElementById('blockEditorModal').style.display = 'flex';
}

function field(label, name, value, type='text') {
    if (type === 'textarea') return `<div class="form-group"><label>${label}</label><textarea name="${name}" rows="3" style="font-size:13px;">${value||''}</textarea></div>`;
    if (type === 'color')    return `<div class="form-group"><label>${label}</label><div style="display:flex;gap:8px;align-items:center;"><input type="color" name="${name}" value="${value||'#000000'}" style="width:48px;height:36px;border:1.5px solid var(--border);border-radius:6px;cursor:pointer;"><input type="text" name="${name}_hex" value="${value||'#000000'}" style="flex:1;" oninput="this.previousElementSibling.value=this.value"></div></div>`;
    if (type === 'select')   return ''; // handled inline
    return `<div class="form-group"><label>${label}</label><input type="${type}" name="${name}" value="${value||''}"></div>`;
}

function buildEditorFields({type, data: d}) {
    const sel = (name, opts, val) => `<div class="form-group"><label>${name}</label><select name="${name.toLowerCase()}">${opts.map(o=>`<option value="${o}"${val===o?' selected':''}>${o}</option>`).join('')}</select></div>`;
    switch(type) {
        case 'logo':     return field('Brand Name','brand',d.brand)+field('Highlight Color','brandColor',d.brandColor,'color')+field('Background','bgColor',d.bgColor,'color');
        case 'hero':     return field('Heading','heading',d.heading)+field('Subtext','subtext',d.subtext,'textarea')+field('Button Text','btnText',d.btnText)+field('Button URL','btnUrl',d.btnUrl)+field('Background','bgColor',d.bgColor,'color');
        case 'heading':  return field('Text','text',d.text)+sel('Level',['h1','h2','h3','h4'],d.level)+sel('Alignment',['left','center','right'],d.align)+field('Color','color',d.color,'color');
        case 'text':     return field('Text','text',d.text,'textarea')+sel('Alignment',['left','center','right'],d.align)+field('Color','color',d.color,'color');
        case 'button':   return field('Button Text','text',d.text)+field('URL','url',d.url)+field('Background','bgColor',d.bgColor,'color')+field('Text Color','textColor',d.textColor,'color')+sel('Alignment',['left','center','right'],d.align);
        case 'image':    return field('Image URL','src',d.src)+field('Alt Text','alt',d.alt)+field('Link URL (optional)','link',d.link);
        case 'divider':  return field('Color','color',d.color,'color')+field('Thickness (px)','thickness',d.thickness,'number');
        case 'spacer':   return field('Height (px)','height',d.height,'number');
        case 'two-col':  return field('Left Column','left',d.left,'textarea')+field('Right Column','right',d.right,'textarea');
        case 'feature':  return field('Icon (emoji)','icon',d.icon)+field('Title','title',d.title)+field('Description','text',d.text,'textarea');
        case 'social':   return d.links.map((l,i)=>field(`${l.name} URL`,`link_${i}`,l.url)).join('');
        case 'footer':   return field('Company Name','companyName',d.companyName)+field('Address','address',d.address)+field('Unsubscribe URL','unsubUrl',d.unsubUrl);
        default:         return '<p style="color:var(--muted);font-size:13px;">No editable fields.</p>';
    }
}

function saveBlockEdit(id) {
    const block = canvasBlocks.find(b => b.id === id);
    if (!block) return;
    document.getElementById('modalFields').querySelectorAll('input,textarea,select').forEach(inp => {
        if (!inp.name || inp.name.endsWith('_hex')) return;
        if (inp.name.startsWith('link_') && block.type === 'social') {
            block.data.links[parseInt(inp.name.split('_')[1])].url = inp.value;
        } else {
            block.data[inp.name] = inp.type === 'number' ? parseInt(inp.value) : inp.value;
        }
    });
    document.getElementById('blockEditorModal').style.display = 'none';
    renderCanvas(); syncCanvasToHtml();
}

// ── Sync canvas → full HTML ───────────────────────────────────
function syncCanvasToHtml() {
    const body = canvasBlocks.map(b => blockToEmailHtml(b.type, b.data)).join('\n');
    const full = `<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f0ede8;color:#2c2c2c;line-height:1.6}.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)}</style></head><body><div class="wrap">${body}</div></body></html>`;
    document.getElementById('html_content').value       = full;
    document.getElementById('html_content_final').value = full;
    updatePreview();
}

function blockToEmailHtml(type, d) {
    switch(type) {
        case 'logo':     return `<div style="background:${d.bgColor};padding:24px 40px;text-align:center;"><span style="font-size:20px;font-weight:800;color:#fff;font-family:sans-serif;">${d.brand.slice(0,-1)}<span style="color:${d.brandColor}">${d.brand.slice(-1)}</span></span></div>`;
        case 'hero':     return `<div style="background:${d.bgColor};padding:48px 40px;text-align:center;"><h1 style="font-size:28px;font-weight:800;color:#0d0d14;margin-bottom:14px;line-height:1.2;">${d.heading}</h1><p style="font-size:15px;color:#555;margin-bottom:24px;max-width:420px;margin-left:auto;margin-right:auto;line-height:1.7;">${d.subtext}</p><a href="${d.btnUrl}" style="display:inline-block;background:#0d0d14;color:#fff;text-decoration:none;padding:13px 32px;border-radius:8px;font-weight:700;font-size:14px;">${d.btnText}</a></div>`;
        case 'heading':  return `<div style="padding:16px 40px 8px;"><${d.level} style="font-size:${d.level==='h1'?'28':d.level==='h2'?'22':'18'}px;font-weight:800;color:${d.color};text-align:${d.align};margin:0;">${d.text}</${d.level}></div>`;
        case 'text':     return `<div style="padding:8px 40px 16px;"><p style="font-size:15px;color:${d.color};line-height:1.7;text-align:${d.align};margin:0;">${d.text}</p></div>`;
        case 'button':   return `<div style="padding:16px 40px;text-align:${d.align};"><a href="${d.url}" style="display:inline-block;background:${d.bgColor};color:${d.textColor};text-decoration:none;padding:13px 32px;border-radius:8px;font-weight:700;font-size:14px;">${d.text}</a></div>`;
        case 'image':    return d.link?`<a href="${d.link}"><img src="${d.src}" alt="${d.alt}" style="width:100%;display:block;"></a>`:`<img src="${d.src}" alt="${d.alt}" style="width:100%;display:block;">`;
        case 'divider':  return `<div style="padding:8px 40px;"><hr style="border:none;border-top:${d.thickness}px solid ${d.color};margin:0;"></div>`;
        case 'spacer':   return `<div style="height:${d.height}px;"></div>`;
        case 'two-col':  return `<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr><td style="width:50%;padding:16px 20px 16px 40px;font-size:14px;color:#555;line-height:1.6;vertical-align:top;">${d.left}</td><td style="width:50%;padding:16px 40px 16px 20px;font-size:14px;color:#555;line-height:1.6;vertical-align:top;">${d.right}</td></tr></table>`;
        case 'feature':  return `<div style="padding:12px 40px;display:flex;align-items:flex-start;gap:16px;"><div style="width:44px;height:44px;min-width:44px;background:#d4a84320;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;">${d.icon}</div><div><div style="font-weight:700;font-size:15px;color:#0d0d14;margin-bottom:4px;">${d.title}</div><div style="font-size:14px;color:#666;line-height:1.6;">${d.text}</div></div></div>`;
        case 'social':   return `<div style="padding:16px 40px;text-align:center;">${d.links.map(l=>`<a href="${l.url}" style="display:inline-block;margin:0 6px;background:${l.color};color:#fff;text-decoration:none;padding:7px 16px;border-radius:20px;font-size:12px;font-weight:700;">${l.name}</a>`).join('')}</div>`;
        case 'footer':   return `<div style="background:#0d0d14;padding:24px 40px;text-align:center;"><p style="color:rgba(255,255,255,.5);font-size:12px;margin-bottom:6px;">© 2025 ${d.companyName}</p><p style="color:rgba(255,255,255,.3);font-size:11px;margin-bottom:6px;">${d.address}</p><a href="${d.unsubUrl}" style="color:rgba(255,255,255,.3);font-size:11px;">Unsubscribe</a></div>`;
        default:         return '';
    }
}

// ── Tab switching ─────────────────────────────────────────────
function switchTab(mode) {
    editorMode = mode;
    ['builder','visual','html'].forEach(m => {
        document.getElementById('tab-'+m).style.background = m===mode?'var(--ink)':'var(--cream)';
        document.getElementById('tab-'+m).style.color      = m===mode?'#fff':'var(--muted)';
        document.getElementById('editor-'+m).style.display = m===mode?'block':'none';
    });
    document.getElementById('blockPalette').style.display  = mode==='builder'?'block':'none';
    document.getElementById('clearBtn').style.display      = mode==='builder'?'inline-flex':'none';
    if (mode === 'visual') renderEditableFrame(document.getElementById('html_content_final').value || document.getElementById('html_content').value);
    if (mode === 'html')   updatePreview();
}

// ── Click-to-edit iframe ──────────────────────────────────────
function renderEditableFrame(html) {
    if (!html) return;
    const frame = document.getElementById('editableFrame');
    const doc   = frame.contentDocument || frame.contentWindow.document;
    const inject = html.includes('</body>') ? html.replace('</body>',
        `<script>document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('p,h1,h2,h3,h4,h5,h6,span,a,td,li,div,strong,em,b,i').forEach(function(el){if(el.children.length===0&&el.textContent.trim()){el.setAttribute('contenteditable','true');el.style.cursor='text';el.addEventListener('focus',function(){this.style.outline='2px dashed #d4a843';this.style.outlineOffset='2px';});el.addEventListener('blur',function(){this.style.outline='none';parent.syncFromFrame();});el.addEventListener('keyup',function(){parent.syncFromFrame();});}});});<\/script></body>`) : html;
    doc.open(); doc.write(inject); doc.close();
}

window.syncFromFrame = function() {
    const doc = document.getElementById('editableFrame').contentDocument;
    const html = doc.documentElement.outerHTML;
    document.getElementById('html_content_final').value = html;
    document.getElementById('html_content').value       = html;
    updatePreview();
};

// ── Preview ───────────────────────────────────────────────────
function updatePreview() {
    const html = document.getElementById('html_content_final').value || document.getElementById('html_content').value;
    if (!html) return;
    const doc = document.getElementById('previewFrame').contentDocument;
    doc.open(); doc.write(html); doc.close();
}
function refreshPreview() { updatePreview(); }

document.addEventListener('input', function(e) {
    if (e.target.id === 'html_content') {
        document.getElementById('html_content_final').value = e.target.value;
        updatePreview();
    }
});

// ── Submit ────────────────────────────────────────────────────
document.getElementById('templateForm').addEventListener('submit', function() {
    if (editorMode==='builder') syncCanvasToHtml();
    else if (editorMode==='visual') window.syncFromFrame();
    else document.getElementById('html_content_final').value = document.getElementById('html_content').value;
});

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const existing = document.getElementById('html_content_final').value;
    if (existing) { switchTab('visual'); }
    else          { switchTab('builder'); renderCanvas(); }
    updatePreview();
});
</script>
@endpush