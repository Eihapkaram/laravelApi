<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // ➕ إضافة تقييم جديد
    public function AddReviwes(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        Review::create([
            'comment' => $request->comment,
            'product_id' => $id,
            'user_id' => auth()->id(),
        ]);

        // جلب المنتج مع التقييمات الخاصة به لتحديث الـ Pinia store مباشرة
        $data = product::with('productReviwes')->find($id);

        return response()->json([
            'massege' => 'add reviwes is done',
            'data' => [$data], // وضعناه داخل مصفوفة تماشياً مع الـ Frontend لديك
        ]);
    }

    // ✏️ تعديل تقييم
    public function UpdateReviwes(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        // جلب الريفيو أولاً للتأكد من وجوده
        $review = Review::find($id);

        if (! $review) {
            return response()->json(['massege' => 'Review not found'], 404);
        }

        // ✅ تم إصلاح الشرط هنا باستخدام المقارنة (==) لضمان أن صاحب الريفيو فقط هو من يملك صلاحية التعديل
        if (auth()->id() == $review->user_id) {
            $review->update([
                'comment' => $request->comment,
            ]);

            // جلب بيانات المنتج المحدثة
            $data = product::with('productReviwes')->find($review->product_id);

            return response()->json([
                'massege' => 'edit reviwes is done',
                'data' => [$data],
            ]);
        }

        return response()->json([
            'massege' => "don't can edit this reviews",
        ], 403);
    }

    // ❌ حذف تقييم
    public function DeleteReviwes($id, $reviweid)
    {
        // جلب الريفيو المطلوب حذفه مباشرة
        $review = Review::where('id', $reviweid)->where('product_id', $id)->first();

        if (! $review) {
            return response()->json([
                'message' => 'Review not found',
            ], 404);
        }

        // ✅ حماية الحذف: التأكد من أن المستخدم الحالي هو صاحب الريفيو
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete this review',
            ], 403);
        }

        // تنفيذ الحذف
        $review->delete();

        // جلب التقييمات المتبقية لهذا المنتج فقط لإرسالها للواجهة
        $remainingReviews = Review::where('product_id', $id)->get();

        return response()->json([
            'massege' => 'delete reviwe done',
            'reviwesNow' => $remainingReviews,
        ]);
    }

    // 🔍 عرض تقييمات منتج معين (Paginatited)
    public function showProReviwes($id)
    {
        $product = product::findOrFail($id);

        // 1. حساب العدد الإجمالي للمراجعات الخاصة بهذا المنتج فقط
        $totalReviewsCount = $product->productReviwes()->count();

        // 2. جلب المراجعات مع الـ Pagination والـ User المرتط بها
        $allproreview = $product->productReviwes()
            ->with('user')
            ->latest() // ترتيبها من الأحدث للأقدم
            ->paginate(10);

        return response()->json([
            'massege' => 'show all review for this product done',
            'total_reviews_count' => $totalReviewsCount, // الحقل الجديد للعدد الإجمالي
            'Proreviwes' => $allproreview,
        ]);
    }
}
