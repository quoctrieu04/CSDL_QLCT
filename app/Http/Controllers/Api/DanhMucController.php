<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Model mới
use App\Models\InCategory;
use App\Models\OutCategory;

class DanhMucController extends Controller
{
    private function pickModel(?string $loai)
    {
        return match ($loai) {
            'thu', 'in'  => InCategory::class,
            'chi', 'out' => OutCategory::class,
            default      => null,
        };
    }

    // GET /api/categories?loai=chi|thu  (hoặc ?type=out|in)
    public function index(Request $r)
    {
        $loai = $r->query('loai', $r->query('type', 'chi')); // mặc định chi (out)
        $model = $this->pickModel($loai) ?? abort(422, 'loai must be chi|thu');

        $q = $model::query()
            ->where('user_id', $r->user()->id)
            ->orderBy('title');           // bảng của bạn dùng cột 'title'

        if ($s = $r->query('search')) {
            $q->where('title', 'like', '%'.str_replace('%','\%',$s).'%');
        }

        return response()->json($q->paginate($r->integer('per_page', 50)));
    }

    // POST /api/categories
    // Body ví dụ:
    // { "loai": "chi", "title": "Ăn uống" }
    // hoặc { "type": "out", "title": "Ăn uống" }
    public function store(Request $r)
{
    $loai = $r->input('loai', $r->input('type', 'chi'));
    $model = $this->pickModel($loai) ?? abort(422, 'loai must be chi|thu');

    $data = $r->validate([
        'title' => 'required|string|max:255',
    ]);

    // 1️⃣ Tạo danh mục
    $row = $model::create([
        'user_id' => $r->user()->id,
        'title'   => $data['title'],
    ]);

    // 2️⃣ Nếu là danh mục chi (OutCategory) → tạo thêm Budget tương ứng
    if (in_array($loai, ['chi', 'out'])) {
        \App\Models\Budget::firstOrCreate(
            [
                'user_id'     => $r->user()->id,
                'category_id' => $row->id,
            ],
            [
                'title'  => $data['title'],
                'amount' => 0,
            ]
        );
    }

    return response()->json($row, 201);
}


    // GET /api/categories/{id}?loai=chi|thu
    public function show(Request $r, $id)
    {
        $loai = $r->query('loai', $r->query('type', 'chi'));
        $model = $this->pickModel($loai) ?? abort(422, 'loai must be chi|thu');

        $row = $model::where('id', $id)
            ->where('user_id', $r->user()->id)
            ->firstOrFail();

        return response()->json($row);
    }

    // PUT/PATCH /api/categories/{id}?loai=chi|thu
    public function update(Request $r, $id)
    {
        $loai = $r->query('loai', $r->query('type', 'chi'));
        $model = $this->pickModel($loai) ?? abort(422, 'loai must be chi|thu');

        $row = $model::where('id', $id)
            ->where('user_id', $r->user()->id)
            ->firstOrFail();

        $data = $r->validate([
            'title' => 'sometimes|string|max:255',
            // thêm color/icon nếu có
        ]);

        $row->update($data);
        return response()->json($row->fresh());
    }

    // DELETE /api/categories/{id}?loai=chi|thu
    public function destroy(Request $r, $id)
    {
        $loai = $r->query('loai', $r->query('type', 'chi'));
        $model = $this->pickModel($loai) ?? abort(422, 'loai must be chi|thu');

        $row = $model::where('id', $id)
            ->where('user_id', $r->user()->id)
            ->firstOrFail();

        $row->delete();
        return response()->noContent();
    }
}
