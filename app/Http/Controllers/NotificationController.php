<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    /**
     * عرض كل الإشعارات للمستخدم الحالي
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(10);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markAsRead($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();

            return response()->json(['message' => 'تم تحديد الإشعار كمقروء ✅']);
        }

        return response()->json(['message' => 'الإشعار غير موجود ❌'], 404);
    }

    /**
     * حذف إشعار
     */
    public function destroy($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->delete();

            return response()->json(['message' => 'تم حذف الإشعار بنجاح 🗑️']);
        }

        return response()->json(['message' => 'الإشعار غير موجود ❌'], 404);
    }

    /**
     * تحديد كل الإشعارات كمقروءة
     */
    public function markAllAsRead()
    {
        $user = auth()->user();

        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم تحديد كل الإشعارات كمقروءة ✅']);
    }

    /**
     * 🧑‍💼 إرسال إشعار من الأدمن
     */
    public function sendByAdmin(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بإرسال إشعارات ❌'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // لو الإدمن محدد مستخدم معين
        if ($request->user_id) {
            $targetUser = User::find($request->user_id);
            $targetUser->notify(new AdminNotification($request->title, $request->message));

            return response()->json(['message' => 'تم إرسال الإشعار للمستخدم المحدد ✅']);
        }

        // أو لكل المستخدمين
        $users = User::where('role', '!=', 'admin')->get();
        Notification::send($users, new AdminNotification($request->title, $request->message));

        return response()->json(['message' => 'تم إرسال الإشعار لكل المستخدمين ✅']);
    }
}
