<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    // عرض الإعدادات
    public function index()
    {
        $settings = Setting::first();

        if (! $settings) {
            return response()->json(['error' => 'Settings not found'], 404);
        }

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    // إنشاء إعدادات جديدة
    public function create(Request $request)
    {
        $request->validate([
            'site_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'signature' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'email' => 'nullable|email',
            'facebook' => 'nullable|url',
            'instgrame' => 'nullable|url',
            'twiter' => 'nullable|url',
            'whatsApp' => 'nullable|url',
            'phone1' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'hotphone' => 'nullable|string|max:20',
            'location' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'shipping_and_return_policy' => 'nullable|string',
            'privacy_policy' => 'nullable|string',

        ]);

        try {
            $settings = new Setting;

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $path = $file->storeAs('settings', time().'_logo.'.$file->getClientOriginalExtension(), 'public');
                $settings->logo = $path;
            }

            if ($request->hasFile('signature')) {
                $file = $request->file('signature');
                $path = $file->storeAs('settings', time().'_signature.'.$file->getClientOriginalExtension(), 'public');
                $settings->signature = $path;
            }

            // باقي الحقول
            $settings->site_name = $request->site_name;
            $settings->email = $request->email;
            $settings->facebook = $request->facebook;
            $settings->instgrame = $request->instgrame;
            $settings->twiter = $request->twiter;
            $settings->whatsApp = $request->whatsApp;
            $settings->phone1 = $request->phone1;
            $settings->phone2 = $request->phone2;
            $settings->hotphone = $request->hotphone;
            $settings->location = $request->location;
            $settings->terms_and_conditions = $request->terms_and_conditions;
            $settings->shipping_and_return_policy = $request->shipping_and_return_policy;
            $settings->privacy_policy = $request->privacy_policy;

            $settings->save();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الإعدادات بنجاح',
                'settings' => $settings,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'error' => 'فشل في رفع الإعدادات',
                'details' => $th->getMessage(),
            ], 500);
        }
    }

    // تحديث الإعدادات
    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'email' => 'nullable|email',
            'facebook' => 'nullable|url',
            'instgrame' => 'nullable|url',
            'twiter' => 'nullable|url',
            'whatsApp' => 'nullable|url',
            'phone1' => 'nullable|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'hotphone' => 'nullable|string|max:20',
            'location' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'shipping_and_return_policy' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
        ]);

        $settings = Setting::first();

        if (! $settings) {
            return response()->json(['error' => 'Settings not found'], 404);
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }
            $imageName = time().'_'.uniqid().'.'.$request->file('logo')->getClientOriginalExtension();
            $path = $request->file('logo')->storeAs('settings', $imageName, 'public');
            $settings->logo = $path;
        }

        if ($request->hasFile('signature')) {
            if ($settings->signature && Storage::disk('public')->exists($settings->signature)) {
                Storage::disk('public')->delete($settings->signature);
            }
            $imageName = time().'_'.uniqid().'.'.$request->file('signature')->getClientOriginalExtension();
            $path = $request->file('signature')->storeAs('settings', $imageName, 'public');
            $settings->signature = $path;
        }

        // تحديث باقي الحقول
        $settings->site_name = $request->site_name;
        $settings->email = $request->email;
        $settings->facebook = $request->facebook;
        $settings->instgrame = $request->instgrame;
        $settings->twiter = $request->twiter;
        $settings->whatsApp = $request->whatsApp;
        $settings->phone1 = $request->phone1;
        $settings->phone2 = $request->phone2;
        $settings->hotphone = $request->hotphone;
        $settings->location = $request->location;
        $settings->terms_and_conditions = $request->terms_and_conditions;
        $settings->shipping_and_return_policy = $request->shipping_and_return_policy;
        $settings->privacy_policy = $request->privacy_policy;

        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث إعدادات الموقع بنجاح!',
            'settings' => $settings,
        ]);
    }
}
