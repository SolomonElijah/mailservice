@extends('layouts.app')
@section('title', 'Email Templates')

@section('content')
<div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 class="page-title">Templates 🎨</h1>
        <p class="page-subtitle">Manage reusable email templates for your campaigns</p>
    </div>
    <a href="{{ route('templates.create') }}" class="btn btn-primary">+ New Template</a>
</div>

@foreach($categories as $key => $cat)
    @if(isset($templates[$key]) && $templates[$key]->count())
    <div style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <span style="font-size:20px;">{{ $cat['icon'] }}</span>
            <h2 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;">{{ $cat['label'] }}</h2>
            <span style="background:var(--gold-dim);color:var(--gold);font-size:11px;font-weight:700;padding:2px 8px;border-radius:100px;">{{ $templates[$key]->count() }}</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            @foreach($templates[$key] as $template)
            <div class="card" style="position:relative;">
                @if($template->is_default)
                <div style="position:absolute;top:12px;right:12px;background:var(--gold-dim);color:var(--gold);font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:3px 8px;border-radius:100px;">
                    Built-in
                </div>
                @endif

                <div style="padding:20px 20px 16px;">
                    <div style="font-size:20px;margin-bottom:10px;">{{ $cat['icon'] }}</div>
                    <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:4px;">{{ $template->name }}</h3>
                    <p style="font-size:12px;color:var(--muted);margin-bottom:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $template->subject }}
                    </p>
                    <div style="font-size:11px;color:var(--muted);">
                        Updated {{ $template->updated_at->diffForHumans() }}
                    </div>
                </div>

                <div style="border-top:1px solid var(--border);padding:12px 20px;display:flex;gap:8px;align-items:center;">
                    <a href="{{ route('templates.edit', $template) }}" class="btn btn-secondary btn-sm">✏️ Edit</a>
                    <a href="{{ route('templates.preview', $template) }}" target="_blank" class="btn btn-secondary btn-sm">👁 Preview</a>

                    <form method="POST" action="{{ route('templates.destroy', $template) }}" style="margin-left:auto;" onsubmit="return confirm('Delete this template?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
@endforeach

@if($templates->isEmpty())
<div class="card" style="text-align:center;padding:60px 24px;">
    <div style="font-size:48px;margin-bottom:16px;">🎨</div>
    <h3 style="font-family:'Syne',sans-serif;font-size:18px;margin-bottom:8px;">No templates yet</h3>
    <p style="color:var(--muted);font-size:14px;margin-bottom:24px;">Create your first template or seed the built-in ones.</p>

    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('templates.create') }}" class="btn btn-primary">+ Create Template</a>

        <form method="POST" action="{{ route('templates.seed') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-secondary">✨ Load Built-in Templates</button>
        </form>
    </div>
</div>
@else
<div style="text-align:center;margin-top:8px;">
    <form method="POST" action="{{ route('templates.seed') }}" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm">✨ Re-load Built-in Templates</button>
    </form>
</div>
@endif
@endsection