<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\User;
use App\Notifications\OfferCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    // ✅ عرض كل العروض (مع فلترة العروض الفعالة فقط)
    public function index()
    {
        $offers = Offer::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($offers);
    }

    // ✅ عرض عرض معين
    public function show($id)
    {
        $id = (int)$id; // تأمين الـ ID من أي محاولات حقن
        $offer = Offer::findOrFail($id);

        return response()->json($offer);
    }

    // ✅ جلب العروض الحالية فقط (الفعالة الآن)
    public function activeOffers()
    {
        $offers = Offer::select('id', 'product_id', 'banner')
            ->where('is_active', 1)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($offers);
    }

    // ✅ إنشاء عرض جديد
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // تأمين الحجم والنوع
            'product_id' => 'nullable|exists:products,id',
            'discount_value' => 'nullable|numeric',
            'discount_type' => 'nullable|in:percent,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // رفع الصورة الرئيسية باسم فريد مشفر للأمان
        $path = null;
        if ($request->hasFile('banner')) {
            $imageExtension = $request->file('banner')->getClientOriginalExtension();
            $imageName = time() . '_' . uniqid() . '.' . $imageExtension;
            $path = $request->file('banner')->storeAs('offersbanner', $imageName, 'public');
        }

        $offer = Offer::create([
            'title' => $request->title,
            'description' => $request->description,
            'banner' => $path,
            'product_id' => $request->product_id,
            'discount_value' => $request->discount_value,
            'discount_type' => $request->discount_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active ?? true,
        ]);

        // 🌟 جلب المستخدمين باستبعاد الـ admin والـ supplier معاً
        $users = User::whereNotIn('role', ['admin', 'supplier'])->get();

        foreach ($users as $user) {
            $user->notify(new OfferCreatedNotification($offer));
        }

        return response()->json([
            'message' => 'تم إنشاء العرض بنجاح',
            'offer' => $offer,
        ], 201);
    }

    // ✅ تحديث عرض (ذكي وآمن)
    public function update(Request $request, $id)
    {
        $id = (int)$id;
        $offer = Offer::findOrFail($id);

        // استخدام sometimes لتحديث الحقول المرسلة فقط دون تصفير البقية
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'product_id' => 'nullable|exists:products,id',
            'discount_value' => 'nullable|numeric',
            'discount_type' => 'nullable|in:percent,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // تجميع البيانات المحدثة فقط مع الاحتفاظ بالقديم إذا لم يُرسل
        $data = [];
        $fields = ['title', 'description', 'product_id', 'discount_value', 'discount_type', 'start_date', 'end_date', 'is_active'];
        
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        // فحص وتحديث البنر: يتم فقط إذا تم رفع ملف حقيقي لمنع الـ 422 والحفاظ على البنر القديم
        if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            
            // Validation يدوي للبنر المرفوع حديثاً
            $request->validate([
                'banner' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($offer->banner) {
                Storage::disk('public')->delete($offer->banner);
            }

            $imageExtension = $request->file('banner')->getClientOriginalExtension();
            $imageName = time() . '_' . uniqid() . '.' . $imageExtension;
            $path = $request->file('banner')->storeAs('offersbanner', $imageName, 'public');
            $data['banner'] = $path;
        }

        // تحديث البيانات في قاعدة البيانات
        $offer->update($data);

        return response()->json([
            'message' => 'تم تحديث العرض بنجاح',
            'offer' => $offer,
        ]);
    }

    // ✅ حذف عرض
    public function destroy($id)
    {
        $id = (int)$id;
        $offer = Offer::findOrFail($id);

        if ($offer->banner) {
            Storage::disk('public')->delete($offer->banner);
        }

        $offer->delete();

        return response()->json(['message' => 'تم حذف العرض بنجاح']);
    }
}
