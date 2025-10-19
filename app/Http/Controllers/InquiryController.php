<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\NewInquiryNotification;


class InquiryController extends Controller
{
    // إرسال استفسار جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::create([
            'user_id' => auth()->id(),
            ...$validated
        ]);
$admins = User::where('role', 'admin')->get();
    foreach ($admins as $admin) {
        $admin->notify(new NewInquiryNotification($inquiry));
    }
        return response()->json([
            'message' => 'تم إرسال استفسارك بنجاح، سنقوم بالرد قريبًا.',
            'inquiry' => $inquiry
        ]);
    }

    // عرض كل الاستفسارات (للمشرف فقط)
    public function index()
    {
        $inquiries = Inquiry::latest()->get();
        return response()->json($inquiries);
    }

    // تحديث حالة الاستفسار
    public function updateStatus($id, Request $request)
    {
        $request->validate(['status' => 'required|in:pending,replied,closed']);
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->update(['status' => $request->status]);

        return response()->json(['message' => 'تم تحديث حالة الاستفسار.']);
    }
}
