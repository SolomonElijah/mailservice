<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::where('user_id', Auth::id())
            ->latest()
            ->get()
            ->groupBy('category');

        $categories = EmailTemplate::categories();

        return view('templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        $categories = EmailTemplate::categories();
        return view('templates.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:100',
            'category'     => 'required|string',
            'subject'      => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_content'=> 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        EmailTemplate::create([
            'user_id'       => Auth::id(),
            'name'          => $request->name,
            'category'      => $request->category,
            'subject'       => $request->subject,
            'html_content'  => $request->html_content,
            'plain_content' => $request->plain_content,
        ]);

        return redirect()->route('templates.index')
            ->with('success', "Template \"{$request->name}\" created successfully!");
    }

    public function edit(EmailTemplate $template)
    {
        $this->authorize($template);
        $categories = EmailTemplate::categories();
        return view('templates.edit', compact('template', 'categories'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $this->authorize($template);

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:100',
            'category'     => 'required|string',
            'subject'      => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_content'=> 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $template->update($request->only('name', 'category', 'subject', 'html_content', 'plain_content'));

        return redirect()->route('templates.index')
            ->with('success', "Template updated successfully!");
    }

    public function destroy(EmailTemplate $template)
    {
        $this->authorize($template);
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function preview(EmailTemplate $template)
    {
        $this->authorize($template);
        return response($template->html_content);
    }

    // Seed built-in templates for new users
    public static function seedDefaults(int $userId): void
    {
        $defaults = [
            [
                'name'      => 'Professional Marketing',
                'category'  => 'marketing',
                'subject'   => '🚀 Exciting News from {{company}}',
                'html_content' => self::marketingTemplate(),
            ],
            [
                'name'      => 'Cold Outreach',
                'category'  => 'cold-email',
                'subject'   => 'Quick question, {{first_name}}',
                'html_content' => self::coldEmailTemplate(),
            ],
            [
                'name'      => 'Newsletter',
                'category'  => 'newsletter',
                'subject'   => '📰 {{company}} Newsletter — {{month}}',
                'html_content' => self::newsletterTemplate(),
            ],
            [
                'name'      => 'Notification',
                'category'  => 'notification',
                'subject'   => '🔔 Important Update from {{company}}',
                'html_content' => self::notificationTemplate(),
            ],
            [
                'name'      => 'Transactional',
                'category'  => 'transactional',
                'subject'   => 'Your {{company}} confirmation',
                'html_content' => self::transactionalTemplate(),
            ],
        ];

        foreach ($defaults as $t) {
            EmailTemplate::create(array_merge($t, [
                'user_id'    => $userId,
                'is_default' => true,
            ]));
        }
    }

    private function authorize(EmailTemplate $template): void
    {
        abort_if($template->user_id != Auth::id(), 403);
    }

    // ═══════════════════════════════════════
    // Built-in HTML Templates
    // ═══════════════════════════════════════

    private static function marketingTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f0ede8;color:#2c2c2c;line-height:1.6}
.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.bar{height:4px;background:linear-gradient(90deg,#0d0d14,#d4a843)}
.header{background:#0d0d14;padding:36px 40px;text-align:center}
.header .brand{font-size:22px;font-weight:800;color:#fff;letter-spacing:-.3px}
.header .brand span{color:#d4a843}
.hero{padding:48px 40px;text-align:center;background:#fff}
.hero h1{font-size:30px;font-weight:800;color:#0d0d14;line-height:1.2;margin-bottom:16px}
.hero h1 span{color:#d4a843}
.hero p{font-size:16px;color:#555;max-width:440px;margin:0 auto 28px;line-height:1.7}
.cta{display:inline-block;background:#0d0d14;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:8px}
.features{padding:32px 40px;background:#faf8f4}
.feature{display:flex;gap:16px;margin-bottom:24px;align-items:flex-start}
.feat-icon{width:44px;height:44px;min-width:44px;background:#d4a84320;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
.feat-title{font-weight:700;font-size:15px;color:#0d0d14;margin-bottom:4px}
.feat-text{font-size:14px;color:#666;line-height:1.6}
.footer{background:#0d0d14;padding:24px 40px;text-align:center;font-size:12px;color:rgba(255,255,255,.35)}
.footer a{color:rgba(255,255,255,.4)}
</style></head>
<body>
<div class="wrap">
<div class="bar"></div>
<div class="header"><div class="brand">Your<span>Brand</span></div></div>
<div class="hero">
  <h1>Something <span>amazing</span><br>is coming your way</h1>
  <p>We're thrilled to share our latest update with you. This is the message your audience has been waiting for.</p>
  <a href="#" class="cta">Discover More →</a>
</div>
<div class="features">
  <div class="feature">
    <div class="feat-icon">🚀</div>
    <div><div class="feat-title">Feature One</div><div class="feat-text">Describe your first key benefit or announcement here. Keep it concise and compelling.</div></div>
  </div>
  <div class="feature">
    <div class="feat-icon">💡</div>
    <div><div class="feat-title">Feature Two</div><div class="feat-text">Your second highlight goes here. Focus on the value you're delivering to the reader.</div></div>
  </div>
  <div class="feature">
    <div class="feat-icon">🎯</div>
    <div><div class="feat-title">Feature Three</div><div class="feat-text">Round it off with a strong third point that drives action or builds excitement.</div></div>
  </div>
</div>
<div class="footer">
  <p>© 2025 YourBrand. All rights reserved.</p>
  <p style="margin-top:6px"><a href="#">Unsubscribe</a> · <a href="#">Privacy Policy</a></p>
</div>
</div>
</body></html>
HTML;
    }

    private static function coldEmailTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Georgia',serif;background:#f5f5f0;color:#2c2c2c;line-height:1.7}
.wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:4px;box-shadow:0 2px 16px rgba(0,0,0,.06)}
.header{padding:32px 40px 0;border-top:3px solid #0d0d14}
.sender{display:flex;align-items:center;gap:12px;margin-bottom:28px}
.avatar{width:44px;height:44px;background:#0d0d14;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#d4a843;font-family:sans-serif}
.sender-info{font-size:13px;color:#666}
.sender-name{font-weight:700;font-size:15px;color:#0d0d14}
.body{padding:0 40px 36px}
.body p{font-size:15px;color:#3a3a3a;margin-bottom:16px;line-height:1.8}
.highlight{background:#fdf8e8;border-left:3px solid #d4a843;padding:14px 18px;border-radius:0 6px 6px 0;margin:20px 0;font-size:14px;color:#555}
.cta-wrap{margin:28px 0}
.cta{display:inline-block;background:#0d0d14;color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px;font-family:sans-serif}
.sig{border-top:1px solid #eee;padding:20px 40px;font-size:13px;color:#666}
.sig strong{color:#0d0d14;font-size:14px}
</style></head>
<body>
<div class="wrap">
<div class="header">
  <div class="sender">
    <div class="avatar">JD</div>
    <div><div class="sender-name">Jane Doe</div><div class="sender-info">Head of Growth · YourCompany</div></div>
  </div>
</div>
<div class="body">
  <p>Hi {{first_name}},</p>
  <p>I came across {{their_company}} and was genuinely impressed by what you're doing in the {{industry}} space. I wanted to reach out because I think there's a real opportunity here.</p>
  <div class="highlight">We helped companies like yours achieve <strong>3x more leads</strong> in under 90 days — without increasing ad spend.</div>
  <p>I'd love to show you exactly how we did it. Would you be open to a quick 15-minute call this week or next?</p>
  <p>No pressure at all — just a conversation to see if there's a fit.</p>
  <div class="cta-wrap"><a href="#" class="cta">Book a 15-min Call →</a></div>
  <p>Looking forward to connecting,</p>
</div>
<div class="sig">
  <strong>Jane Doe</strong><br>
  Head of Growth, YourCompany<br>
  jane@yourcompany.com · +1 (555) 000-0000
</div>
</div>
</body></html>
HTML;
    }

    private static function newsletterTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f0ede8;color:#2c2c2c;line-height:1.6}
.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.header{background:#0d0d14;padding:28px 40px;display:flex;align-items:center;justify-content:space-between}
.brand{font-size:20px;font-weight:800;color:#fff;font-family:sans-serif}
.brand span{color:#d4a843}
.issue{font-size:11px;color:rgba(255,255,255,.4);letter-spacing:1px;text-transform:uppercase}
.hero-strip{background:#d4a843;padding:14px 40px}
.hero-strip h2{font-size:20px;font-weight:800;color:#0d0d14}
.hero-strip p{font-size:13px;color:rgba(13,13,20,.6);margin-top:2px}
.section{padding:32px 40px;border-bottom:1px solid #eee}
.section h3{font-size:16px;font-weight:800;color:#0d0d14;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.section p{font-size:14px;color:#555;line-height:1.8;margin-bottom:12px}
.read-more{font-size:13px;color:#d4a843;text-decoration:none;font-weight:700}
.articles{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:32px 40px}
.article{background:#faf8f4;border-radius:8px;padding:16px}
.article-tag{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#d4a843;margin-bottom:6px}
.article-title{font-size:14px;font-weight:700;color:#0d0d14;margin-bottom:6px;line-height:1.4}
.article-text{font-size:12px;color:#777;line-height:1.6}
.footer{background:#0d0d14;padding:24px 40px;text-align:center;font-size:12px;color:rgba(255,255,255,.35)}
</style></head>
<body>
<div class="wrap">
<div class="header">
  <div class="brand">Your<span>Letter</span></div>
  <div class="issue">Issue #12 · January 2025</div>
</div>
<div class="hero-strip">
  <h2>This Week in Brief 📰</h2>
  <p>Your curated roundup of what matters most</p>
</div>
<div class="section">
  <h3>🔥 Top Story</h3>
  <p>Your lead story goes here. Write 2-3 sentences that hook the reader and deliver genuine value. This is the most important piece of content in your newsletter.</p>
  <a href="#" class="read-more">Read full story →</a>
</div>
<div class="articles">
  <div class="article">
    <div class="article-tag">Insight</div>
    <div class="article-title">Article headline goes here</div>
    <div class="article-text">Brief summary of this article. Keep it to 2 sentences.</div>
  </div>
  <div class="article">
    <div class="article-tag">Resource</div>
    <div class="article-title">Second article headline</div>
    <div class="article-text">Brief summary of the second item. Tease value.</div>
  </div>
  <div class="article">
    <div class="article-tag">Tool</div>
    <div class="article-title">Third article goes here</div>
    <div class="article-text">One useful tool or tip your readers will appreciate.</div>
  </div>
  <div class="article">
    <div class="article-tag">Opinion</div>
    <div class="article-title">Fourth article headline</div>
    <div class="article-text">A hot take or opinion piece to spark conversation.</div>
  </div>
</div>
<div class="footer">
  <p>© 2025 YourNewsletter. You're receiving this because you subscribed.</p>
  <p style="margin-top:6px"><a href="#">Unsubscribe</a> · <a href="#">View in browser</a></p>
</div>
</div>
</body></html>
HTML;
    }

    private static function notificationTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f5f5f5;color:#2c2c2c;line-height:1.6}
.wrap{max-width:520px;margin:40px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.08)}
.top-bar{height:4px;background:#1e7e52}
.header{padding:28px 36px;border-bottom:1px solid #eee;display:flex;align-items:center;gap:12px}
.notif-icon{width:48px;height:48px;background:#e8f5ee;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px}
.header-text h2{font-size:17px;font-weight:700;color:#0d0d14}
.header-text p{font-size:12px;color:#999;margin-top:2px}
.body{padding:28px 36px}
.body p{font-size:15px;color:#444;margin-bottom:14px;line-height:1.7}
.info-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin:20px 0}
.info-row{display:flex;justify-content:space-between;font-size:13px;padding:6px 0;border-bottom:1px solid #eee}
.info-row:last-child{border-bottom:none}
.info-label{color:#888}
.info-value{font-weight:600;color:#0d0d14}
.cta-wrap{text-align:center;margin:24px 0}
.cta{display:inline-block;background:#1e7e52;color:#fff;text-decoration:none;font-size:14px;font-weight:700;padding:13px 32px;border-radius:8px}
.footer{background:#f9f9f9;border-top:1px solid #eee;padding:16px 36px;font-size:11px;color:#aaa;text-align:center}
</style></head>
<body>
<div class="wrap">
<div class="top-bar"></div>
<div class="header">
  <div class="notif-icon">🔔</div>
  <div class="header-text"><h2>Important Notification</h2><p>Action may be required</p></div>
</div>
<div class="body">
  <p>Hi {{name}},</p>
  <p>We're reaching out to notify you of an important update regarding your account.</p>
  <div class="info-box">
    <div class="info-row"><span class="info-label">Event</span><span class="info-value">Account Update</span></div>
    <div class="info-row"><span class="info-label">Date</span><span class="info-value">{{date}}</span></div>
    <div class="info-row"><span class="info-label">Status</span><span class="info-value" style="color:#1e7e52">Active</span></div>
  </div>
  <p>If you have any questions or did not expect this notification, please contact our support team immediately.</p>
  <div class="cta-wrap"><a href="#" class="cta">View Details</a></div>
</div>
<div class="footer">This is an automated notification. Please do not reply directly to this email.</div>
</div>
</body></html>
HTML;
    }

    private static function transactionalTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f5f5f5;color:#2c2c2c;line-height:1.6}
.wrap{max-width:520px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.07)}
.header{background:#0d0d14;padding:28px 36px;text-align:center}
.header .brand{font-size:20px;font-weight:800;color:#fff;font-family:sans-serif}
.header .brand span{color:#d4a843}
.check{text-align:center;padding:32px 36px 16px}
.check-circle{width:64px;height:64px;background:#e8f5ee;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:16px}
.check h2{font-size:22px;font-weight:800;color:#0d0d14;margin-bottom:8px}
.check p{font-size:15px;color:#666}
.order-box{margin:0 36px;border:1.5px solid #e5e7eb;border-radius:8px;overflow:hidden}
.order-header{background:#f9fafb;padding:12px 20px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#888;border-bottom:1px solid #e5e7eb}
.order-row{display:flex;justify-content:space-between;padding:12px 20px;border-bottom:1px solid #f3f4f6;font-size:14px}
.order-row:last-child{border-bottom:none}
.order-label{color:#666}
.order-value{font-weight:600;color:#0d0d14}
.total-row{background:#f9fafb;display:flex;justify-content:space-between;padding:14px 20px;font-size:15px;font-weight:800;color:#0d0d14;border-top:2px solid #e5e7eb}
.body{padding:24px 36px}
.body p{font-size:14px;color:#555;line-height:1.7}
.footer{padding:20px 36px;border-top:1px solid #eee;font-size:11px;color:#aaa;text-align:center}
</style></head>
<body>
<div class="wrap">
<div class="header"><div class="brand">Your<span>Brand</span></div></div>
<div class="check">
  <div class="check-circle">✅</div>
  <h2>Order Confirmed!</h2>
  <p>Thank you, {{name}}. Your order has been received.</p>
</div>
<div class="order-box">
  <div class="order-header">Order Summary — #{{order_id}}</div>
  <div class="order-row"><span class="order-label">Product / Service</span><span class="order-value">{{product_name}}</span></div>
  <div class="order-row"><span class="order-label">Quantity</span><span class="order-value">{{quantity}}</span></div>
  <div class="order-row"><span class="order-label">Date</span><span class="order-value">{{date}}</span></div>
  <div class="total-row"><span>Total</span><span>{{total}}</span></div>
</div>
<div class="body">
  <p style="margin-top:16px">A receipt has been sent to {{email}}. If you have any questions about your order, reply to this email or contact support.</p>
</div>
<div class="footer">© 2025 YourBrand · <a href="#" style="color:#aaa">Unsubscribe</a></div>
</div>
</body></html>
HTML;
    }
}
