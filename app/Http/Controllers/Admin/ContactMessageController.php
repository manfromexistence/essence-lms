<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query()->latest();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status') && in_array($request->status, ['new', 'read', 'replied'])) {
            $query->where('status', $request->status);
        }

        $messages = $query->paginate(20)->withQueryString();
        $stats = [
            'total' => ContactMessage::count(),
            'new' => ContactMessage::where('status', 'new')->count(),
            'read' => ContactMessage::where('status', 'read')->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
        ];

        return view('dashboard.contact-messages.index', compact('messages', 'stats'));
    }

    public function show(ContactMessage $message)
    {
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('dashboard.contact-messages.show', compact('message'));
    }

    public function updateStatus(Request $request, ContactMessage $message)
    {
        $request->validate(['status' => 'required|in:new,read,replied']);

        $message->update(['status' => $request->status]);

        return back()->with('success', 'Message status updated.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return redirect()->route('dashboard.contact-messages.index')->with('success', 'Message deleted.');
    }
}
