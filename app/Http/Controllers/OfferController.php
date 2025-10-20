<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\OfferCreatedNotification;
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
        $offer = Offer::findOrFail($id);
        return response()->json($offer);
    }
// ✅ جلب العروض الحالية فقط (الفعالة الآن)
public function activeOffers()
{
    $today = now();

    $offers = Offer::where('is_active', true)
        ->where(function ($query) use ($today) {
            $query->whereNull('start_date')
                ->orWhere('start_date', '<=', $today);
        })
        ->where(function ($query) use ($today) {
            $query->whereNull('end_date')
                ->orWhere('end_date', '>=', $today);
        })
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($offers);
}

    // ✅ إنشاء عرض جديد
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_id' => 'nullable|exists:products,id',
            'discount_value' => 'nullable|numeric',
            'discount_type' => 'nullable|in:percent,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // رفع الصورة الرئيسية
        $path = null;
        if ($request->hasFile('banner')) {
            $image = $request->file('banner')->getClientOriginalName();
            $path = $request->file('banner')->storeAs('offersbanner', $image, 'public');
        }

        $offer = Offer::create([
             
            'title' => $request->title,
            'description' => $request->description,
            'banner' => $path,
            'product_id' => $request->product_id,
            'discount_value' => $request->discount_value,
            'discount_type' =>$request->discount_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active,
      
        ]);
$users = User::where('role', '!=', 'admin')->get();

foreach ($users as $user) {
    $user->notify(new OfferCreatedNotification($offer));
}
        return response()->json([
            'message' => 'تم إنشاء العرض بنجاح',
            'offer' => $offer
        ], 201);
    }

    // ✅ تحديث عرض
    public function update(Request $request, $id)
    {
        $offer = Offer::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_id' => 'nullable|exists:products,id',
            'discount_value' => 'nullable|numeric',
            'discount_type' => 'nullable|in:percent,fixed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('banner')) {
            if ($offer->banner) {
                Storage::disk('public')->delete($offer->banner);
            }
             // رفع الصورة الرئيسية
        $path = null;
            $image = $request->file('banner')->getClientOriginalName();
            $path = $request->file('banner')->storeAs('offersbanner', $image, 'public');
            $data['banner'] = $path;
        }

        $offer->update($data);

        return response()->json([
            'message' => 'تم تحديث العرض بنجاح',
            'offer' => $offer
        ]);
    }

    // ✅ حذف عرض
    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);

        if ($offer->banner) {
            Storage::disk('public')->delete($offer->banner);
        }

        $offer->delete();

        return response()->json(['message' => 'تم حذف العرض بنجاح']);
    }
}
