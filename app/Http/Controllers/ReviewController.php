<?php

namespace App\Http\Controllers;

use App\Models\product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function AddReviwes(Request $request, $id)
    {
        $userid = auth()->user()->id;
        $request->validate([
            'comment' => 'required',
        ]);
        Review::create([
            'comment' => $request->comment,
            'product_id' => $id,
            'user_id' => $userid,
        ]);

        $data = product::where('id', $id)->with('productReviwes')->get();

        return response()->json([
            'massege' => 'add reviwes is done',
            'data' => $data,

        ]);
    }

    public function UpdateReviwes(Request $request, $id)
    {
        $userid = auth()->user()->id;
        $request->validate([
            'comment' => 'required',
        ]);
        if ($userid = Review::find($id)->user_id) {
            Review::find($id)->update([
                'comment' => $request->comment,
                'user_id' => $userid,
            ]);
            $data = product::where('id', Review::find($id)->product_id)->with('productReviwes')->get();

            return response()->json([
                'massege' => 'edit reviwes is done',
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'massege' => "don't can edit this  reviwes",
            ]);
        }
    }

    public function DeleteReviwes($id, $reviweid)
    {
        $allproreview = product::find($id)->productReviwes()->get();
        $s = null;
        foreach ($allproreview as $reviwe) {
            if (! $reviwe->find($reviweid)) {
                return response()->json([
                    'message' => 'Review not found',
                    'reviwesNow' => $reviwe->get(),
                ], 404);
                if ($review->user_id !== auth()->id()) {
                    return response()->json([
                        'message' => 'You cannot delete this review',
                    ], 403);
                }
            }

            $reviwe->destroy($reviweid);

            return response()->json([
                'massege' => 'delete reviwe done',
                'reviwesNow' => $reviwe->get(),
            ]);
        }
    }

public function showProReviwes($id)
{
    $allproreview = product::find($id)->productReviwes()->with('user')->get();

    return response()->json([
        'massege' => 'show all  reviwe for this product done',
        'Proreviwes' => $allproreview,
    ]);
}
}
