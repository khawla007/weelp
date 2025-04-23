<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;

class MediaController extends Controller
{
    public function index()
    {
        return response()->json(Media::all());
    }

    public function store(Request $request)
    {
        $media = Media::create([
            'name' => $request->name,
            'alt_text' => $request->alt_text,
            'url' => $request->url,
        ]);

        return response()->json(['message' => 'Media created successfully', 'data' => $media], 201);
    }

    public function show($id)
    {
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $media->update([
            'name' => $request->name ?? $media->name,
            'alt_text' => $request->alt_text ?? $media->alt_text,
            'url' => $request->url ?? $media->url,
        ]);

        return response()->json(['message' => 'Media updated successfully', 'data' => $media]);
    }

    public function destroy($id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        $media->delete();
        return response()->json(['message' => 'Media deleted successfully']);
    }
}
