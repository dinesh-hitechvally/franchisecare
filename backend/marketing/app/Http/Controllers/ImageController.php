<?php

namespace App\Http\Controllers;

use App\Services\ImageSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ImageController extends Controller
{
    /** Sources the picker can offer right now (keyless ones are always present). */
    public function providers()
    {
        return collect(['all' => 'All free sources'] + ImageSearch::available())->map(fn ($label, $id) => ['id' => $id, 'label' => $label])->values();
    }

    public function search(Request $request, ImageSearch $images)
    {
        $data = $request->validate([
            'q' => 'required|string|max:100',
            'provider' => 'required|string',
            'page' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            // cache briefly to stay inside the providers' free rate limits
            return Cache::remember(
                "img:{$data['provider']}:".md5($data['q']).':'.($data['page'] ?? 1),
                600,
                fn () => $images->search($data['provider'], $data['q'], $data['page'] ?? 1),
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Image search failed: '.$e->getMessage()], 422);
        }
    }
}
