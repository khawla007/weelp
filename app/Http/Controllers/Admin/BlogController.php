<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'publish' => 'required|boolean',
            'featured_image' => 'required|exists:media,id',
            'category_id' => 'required|exists:categories,id',
            'tag_id' => 'required|exists:tags,id',
            'excerpt' => 'required|string',
            'activity_id' => 'required|exists:activities,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $blog = Blog::create([
            'name'           => $request->name,
            'slug'           => Str::slug($request->name),
            'content'        => $request->content,
            'publish'        => $request->publish,
            'featured_image' => $request->featured_image,
            'category_id'    => $request->category_id,
            'tag_id'         => $request->tag_id,
            'excerpt'        => $request->excerpt,
            'activity_id'    => $request->activity_id,
        ]);

        // return response()->json($blog, 201);
        return response()->json([
            'message' => 'Blog created successfully',
            'Blog' => $blog
        ], 201);
        
    }

    // Update an existing blog
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'slug'            => 'sometimes|string|max:255',
            'content'         => 'sometimes|string',
            'publish'         => 'sometimes|boolean',
            'featured_image'  => 'sometimes|exists:media,id',
            'category_id'     => 'sometimes|exists:categories,id',
            'tag_id'          => 'sometimes|exists:tags,id',
            'excerpt'         => 'sometimes|string',
            'activity_id'     => 'sometimes|exists:activities,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        $blog = Blog::findOrFail($id);
    
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $blog->getFillable())) {
                $blog->$key = $value;
            }
        }
    
        $blog->save();
    
        return response()->json([
            'message' => 'Blog updated successfully',
            'blog'    => $blog,
        ], 200);
    }    

    // Get all blogs
    public function index()
    {
        // $blogs = Blog::all();
        $blogs = Blog::with(['media', 'category', 'tag', 'activity.locations.city.region'])->get();

        $formatted = $blogs->map(function ($blog) {
            $activity = $blog->activity;

            // Find primary location from all activity locations
            $primaryLocation = $activity?->locations?->firstWhere('location_type', 'primary');
            $city   = $primaryLocation?->city;
            $region = $city?->region;

            return [
                'id'             => $blog->id,
                'name'           => $blog->name,
                'slug'           => $blog->slug,
                // 'content'        => $blog->content,
                'excerpt'        => $blog->excerpt,
                'publish'        => $blog->publish,
    
                // 'category_id'    => $blog->category_id,
                // 'category'       => [
                //     'id'   => $blog->category->id ?? null,
                //     'name' => $blog->category->name ?? null,
                // ],
    
                // 'tag_id'         => $blog->tag_id,
                // 'tag'            => [
                //     'id'   => $blog->tag->id ?? null,
                //     'name' => $blog->tag->name ?? null,
                // ],
    
                'featured_image' => $blog->featured_image,
                'media'          => [
                    'id'  => $blog->media->id ?? null,
                    'url' => $blog->media->url ?? null,
                ],
    
                // 'activity'       => [
                //     'id'    => $activity?->id,
                //     'name'  => $activity?->name,
                //     'slug'  => $activity?->slug,
                //     'city'  => $city ? [
                //         'id'   => $city->id,
                //         'name' => $city->name,
                //     ] : null,
                //     'region' => $region ? [
                //         'id'   => $region->id,
                //         'name' => $region->name,
                //     ] : null,
                // ],
                'created_at'    => $blog->created_at,
                'updated_at'    => $blog->updated_at,
            ];
        });
    
        return response()->json($formatted, 200);
        // return response()->json($blogs, 200);
    }

    // Get a single blog
    public function show($id)
    {
        $blog = Blog::with([
            'media',
            'category',
            'tag',
            'activity.locations.city.region'
        ])->findOrFail($id);
    
        // Extract primary location
        $primaryLocation = $blog->activity?->locations?->firstWhere('location_type', 'primary');
        $city   = $primaryLocation?->city;
        $region = $city?->region;
    
        return response()->json([
            'id'          => $blog->id,
            'name'        => $blog->name,
            'slug'        => $blog->slug,
            'content'     => $blog->content,
            'excerpt'     => $blog->excerpt,
            'publish'     => $blog->publish,
            'media'       => $blog->media,
            'category_id' => $blog->category_id,
            'category'    => $blog->category,
            'tag_id'      => $blog->tag_id,
            'tag'         => $blog->tag,
            'activity_id' => $blog->activity_id,
            'activity'    => [
                'id'     => $blog->activity?->id,
                'name'   => $blog->activity?->name,
                'slug'   => $blog->activity?->slug,
                'city'   => $city ? [
                    'id'   => $city->id,
                    'name' => $city->name,
                ] : null,
                'region' => $region ? [
                    'id'   => $region->id,
                    'name' => $region->name,
                ] : null,
            ],
        ], 200);
    }    

    // Delete a blog
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();
        return response()->json(['message' => 'Blog deleted successfully'], 200);
    }
}
