<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    // ═══════════════════════════════
    // CONTACT LISTS
    // ═══════════════════════════════

    public function index()
    {
        $lists = ContactList::where('user_id', Auth::id())
            ->withCount(['contacts', 'activeContacts'])
            ->latest()
            ->get();

        $totalContacts     = $lists->sum('contacts_count');
        $totalSubscribed   = $lists->sum('active_contacts_count');
        $totalUnsubscribed = $totalContacts - $totalSubscribed;

        return view('contacts.index', compact(
            'lists', 'totalContacts', 'totalSubscribed', 'totalUnsubscribed'
        ));
    }

    public function storeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        ContactList::create([
            'user_id'     => Auth::id(),
            'name'        => trim($request->name),
            'description' => $request->description,
        ]);

        return back()->with('success', "List \"{$request->name}\" created successfully!");
    }

    public function updateList(Request $request, ContactList $list)
    {
        $this->authorizeList($list);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $list->update([
            'name'        => trim($request->name),
            'description' => $request->description,
        ]);

        return back()->with('success', 'List updated successfully!');
    }

    public function destroyList(ContactList $list)
    {
        $this->authorizeList($list);
        $count = $list->contacts()->count();
        $list->delete();
        return back()->with('success', "List deleted along with {$count} contacts.");
    }

    // ═══════════════════════════════
    // CONTACTS (within a list)
    // ═══════════════════════════════

    public function show(ContactList $list, Request $request)
    {
        $this->authorizeList($list);

        $query = $list->contacts()->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $contacts = $query->paginate(25)->withQueryString();

        return view('contacts.show', compact('list', 'contacts'));
    }

    public function storeContact(Request $request, ContactList $list)
    {
        $this->authorizeList($list);

        $validator = Validator::make($request->all(), [
            'email'   => 'required|email',
            'name'    => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check for duplicate in this list
        $exists = $list->contacts()->where('email', $request->email)->exists();
        if ($exists) {
            return back()->with('error', "Contact {$request->email} already exists in this list.")->withInput();
        }

        $list->contacts()->create([
            'email'   => strtolower(trim($request->email)),
            'name'    => $request->name,
            'company' => $request->company,
            'status'  => 'subscribed',
        ]);

        return back()->with('success', "Contact {$request->email} added successfully!");
    }

    public function updateContact(Request $request, ContactList $list, Contact $contact)
    {
        $this->authorizeList($list);

        $validator = Validator::make($request->all(), [
            'email'   => 'required|email',
            'name'    => 'nullable|string|max:100',
            'company' => 'nullable|string|max:100',
            'status'  => 'required|in:subscribed,unsubscribed,bounced',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $contact->update([
            'email'   => strtolower(trim($request->email)),
            'name'    => $request->name,
            'company' => $request->company,
            'status'  => $request->status,
        ]);

        return back()->with('success', 'Contact updated successfully!');
    }

    public function destroyContact(ContactList $list, Contact $contact)
    {
        $this->authorizeList($list);
        $contact->delete();
        return back()->with('success', 'Contact removed.');
    }

    // ═══════════════════════════════
    // BULK ACTIONS
    // ═══════════════════════════════

    public function bulkAction(Request $request, ContactList $list)
    {
        $this->authorizeList($list);

        $ids    = explode(',', $request->contact_ids ?? '');
        $action = $request->bulk_action;

        if (empty($ids) || !$action) {
            return back()->with('error', 'No contacts selected.');
        }

        $contacts = $list->contacts()->whereIn('id', $ids);

        switch ($action) {
            case 'delete':
                $count = $contacts->count();
                $contacts->delete();
                return back()->with('success', "{$count} contact(s) deleted.");

            case 'unsubscribe':
                $contacts->update(['status' => 'unsubscribed']);
                return back()->with('success', 'Selected contacts unsubscribed.');

            case 'resubscribe':
                $contacts->update(['status' => 'subscribed']);
                return back()->with('success', 'Selected contacts resubscribed.');
        }

        return back()->with('error', 'Unknown action.');
    }

    // ═══════════════════════════════
    // CSV IMPORT
    // ═══════════════════════════════

    public function importForm(ContactList $list)
    {
        $this->authorizeList($list);
        return view('contacts.import', compact('list'));
    }

    public function import(Request $request, ContactList $list)
    {
        $this->authorizeList($list);

        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $file    = $request->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = null;
        $added   = 0;
        $skipped = 0;
        $errors  = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // First row = headers
            if (!$headers) {
                $headers = array_map('strtolower', array_map('trim', $row));
                continue;
            }

            if (empty(array_filter($row))) continue;

            $data = array_combine($headers, array_pad($row, count($headers), ''));

            $email = strtolower(trim($data['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            // Skip duplicates within this list
            if ($list->contacts()->where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            try {
                $list->contacts()->create([
                    'email'   => $email,
                    'name'    => trim($data['name'] ?? $data['first_name'] ?? ''),
                    'company' => trim($data['company'] ?? $data['organisation'] ?? ''),
                    'status'  => 'subscribed',
                ]);
                $added++;
            } catch (\Exception $e) {
                $skipped++;
            }
        }

        fclose($handle);

        $msg = "{$added} contact(s) imported successfully.";
        if ($skipped > 0) $msg .= " {$skipped} skipped (duplicates or invalid).";

        return redirect()->route('contacts.show', $list)->with('success', $msg);
    }

    // ═══════════════════════════════
    // CSV EXPORT
    // ═══════════════════════════════

    public function export(ContactList $list)
    {
        $this->authorizeList($list);

        $contacts = $list->contacts()->get();
        $filename = 'contacts-' . \Str::slug($list->name) . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($contacts) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'name', 'company', 'status', 'created_at']);
            foreach ($contacts as $c) {
                fputcsv($handle, [
                    $c->email,
                    $c->name ?? '',
                    $c->company ?? '',
                    $c->status,
                    $c->created_at->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ═══════════════════════════════
    // PUBLIC UNSUBSCRIBE
    // ═══════════════════════════════

    public function unsubscribe(Request $request)
    {
        $email = base64_decode($request->token ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return view('contacts.unsubscribe', ['status' => 'invalid']);
        }

        $updated = Contact::where('email', $email)->update(['status' => 'unsubscribed']);

        return view('contacts.unsubscribe', [
            'status' => $updated ? 'success' : 'notfound',
            'email'  => $email,
        ]);
    }

    // ═══════════════════════════════
    // Helpers
    // ═══════════════════════════════

    private function authorizeList(ContactList $list): void
    {
        abort_if($list->user_id != Auth::id(), 403);
    }
}
