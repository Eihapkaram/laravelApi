<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    // عرض الإعدادات
    public function index()
    {
        $settings = Setting::first();

        if (!$settings) {
            return response()->json(['error' => 'Settings not found'], 404);
        }

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    // إنشاء إعدادات جديدة
    public function create(Request $request)
    {
        // التحقق من البيانات
        $request->validate([
            'site_name' => 'required|string|max:255',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
            'signature' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        // التأكد من عدم وجود إعدادات مسبقة
        if (Setting::first()) {
            return response()->json(['error' => 'Settings already exist'], 400);
        }

        $settings = new Setting();

        // رفع شعار الموقع
        if ($request->hasFile('logo')) {
            $imageName = time().'_'.uniqid().'.'.$request->file('logo')->getClientOriginalExtension();
            $path = $request->file('logo')->storeAs('settings', $imageName, 'public');
            $settings->logo = $path; // فقط المسار
        }

        // رفع التوقيع
        if ($request->hasFile('signature')) {
            $imageName = time().'_'.uniqid().'.'.$request->file('signature')->getClientOriginalExtension();
            $path = $request->file('signature')->storeAs('settings', $imageName, 'public');
            $settings->signature = $path; // فقط المسار
        }

        // اسم الموقع
        $settings->site_name = $request->site_name;

        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء إعدادات الموقع بنجاح!',
            'settings' => $settings
        ]);
    }

    // تحديث الإعدادات
    public function update(Request $request)
    {
        // التحقق من البيانات
        $request->validate([
            'site_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        $settings = Setting::first();

        if (!$settings) {
            return response()->json(['error' => 'Settings not found'], 404);
        }

        // رفع شعار الموقع إذا موجود
        if ($request->hasFile('logo')) {
            // حذف الصورة القديمة إذا موجودة
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }

            $imageName = time().'_'.uniqid().'.'.$request->file('logo')->getClientOriginalExtension();
            $path = $request->file('logo')->storeAs('settings', $imageName, 'public');
            $settings->logo = $path; // فقط المسار
        }

        // رفع التوقيع إذا موجود
        if ($request->hasFile('signature')) {
            // حذف الصورة القديمة إذا موجودة
            if ($settings->signature && Storage::disk('public')->exists($settings->signature)) {
                Storage::disk('public')->delete($settings->signature);
            }

            $imageName = time().'_'.uniqid().'.'.$request->file('signature')->getClientOriginalExtension();
            $path = $request->file('signature')->storeAs('settings', $imageName, 'public');
            $settings->signature = $path; // فقط المسار
        }

        // تحديث اسم الموقع
        $settings->site_name = $request->site_name;

        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات الموقع بنجاح!',
            'settings' => $settings
        ]);
    }
}
