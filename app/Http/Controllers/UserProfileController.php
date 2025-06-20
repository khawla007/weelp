<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMeta;  // Import UserMeta model
use App\Models\Order;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{

    /**
     * Handle the get user profile request.
    */
    public function show(Request $request)
    {
        $user = User::with(['profile.urls', 'meta', ])->find($request->user()->id);

        if (!$user) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        // return response()->json($profile);
        return response()->json([
            'user'    => $user,
        ]);
    }

    /**
     * Handle the insert/update user profile request.
    */

    public function update(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'nullable|url',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'linkedin_url' => 'nullable|url',
            'username' => 'nullable|string|max:255',
            'interest' => 'nullable|string',
            'bio' => 'nullable|string',

            // URLs validation
            'urls' => 'nullable|array',
            'urls.*.label' => 'nullable|string|max:255',
            'urls.*.url' => 'nullable|url',
        ]);

        $user = $request->user();
        $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);

        $profile->fill($validated);
        $profile->save();
        
        $userMeta = UserMeta::firstOrNew(['user_id' => $user->id]);

        if (isset($validated['username'])) {
            $userMeta->username = $validated['username'];
        }
        if (isset($validated['interest'])) {
            $userMeta->interest = $validated['interest'];
        }
        if (isset($validated['bio'])) {
            $userMeta->bio = $validated['bio'];
        }

        $userMeta->save();  
                        
        if ($request->has('urls')) {
            $incomingUrls = $validated['urls'];
            $existingUrls = $profile->urls()->orderBy('id')->get();
            
            $existingCount = $existingUrls->count();
            $incomingCount = count($incomingUrls);
        
            foreach ($incomingUrls as $index => $urlData) {
                if ($index < $existingCount) {
                    // Update existing URL
                    $existingUrls[$index]->update([
                        'label' => $urlData['label'] ?? $existingUrls[$index]->label,
                        'url' => $urlData['url'] ?? $existingUrls[$index]->url
                    ]);
                } else {
                    // Create new URL entry
                    $profile->urls()->create($urlData);
                }
            }
        
            // If there are extra existing URLs beyond the incoming data, delete them
            if ($existingCount > $incomingCount) {
                for ($i = $incomingCount; $i < $existingCount; $i++) {
                    $existingUrls[$i]->delete();
                }
            }
        }
        
        
        return response()->json([
            'success' => true,
            'profile' => $profile->load('urls'),
            'user_meta' => $userMeta 
        ]);
    }

    public function getUserOrders(Request $request)
    {
        $user = auth()->user();

        $orders = Order::with([
            'payment',
            'emergencyContact',
            'orderable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\Activity::class => [
                        'locations.city.state.country',
                        'mediaGallery.media',
                    ],
                    \App\Models\Package::class => [
                        'locations.city.state.country',
                        'mediaGallery.media',
                    ],
                    \App\Models\Itinerary::class => [
                        'locations.city.state.country',
                        'mediaGallery.media',
                    ],
                ]);
            }
        ])->where('user_id', auth()->id())->latest()->get();

        if ($orders->isEmpty()) {
            return response()->json(['error' => 'No orders found'], 404);
        }

        $userProfile = $user->profile;

        $transformed = $orders->map(function ($order) use ($user, $userProfile) {
            $orderable = $order->orderable;

            $cityName = null;
            $regionName = null;

            // ✅ Load from snapshot if orderable is missing
            // if (!$orderable && $order->item_snapshot_json) {
                $snapshot = is_array($order->item_snapshot_json)
                    ? $order->item_snapshot_json
                    : json_decode($order->item_snapshot_json, true);

                $media = collect($snapshot['media'] ?? [])->map(fn ($mediaLink) => [
                    'name' => null,
                    'alt_text' => $mediaLink['alt'] ?? null,
                    'url' => $mediaLink['url'] ?? null,
                ]);

                $locations = $snapshot['locations'] ?? [];
                $cityName = $locations[0]['city'] ?? null;
                $countryId = null;

                if (!empty($locations[0]['country'])) {
                    $countryId = \App\Models\Country::where('name', $locations[0]['country'])->value('id');
                }

                $region = $countryId
                    ? \App\Models\Region::whereHas('countries', fn ($q) => $q->where('countries.id', $countryId))->first()
                    : null;

                return [
                    'id' => $order->id,
                    'item_id' => $order->orderable_id,
                    'status' => $order->status,
                    'travel_date' => $order->travel_date,
                    'preferred_time' => $order->preferred_time,
                    'number_of_adults' => $order->number_of_adults,
                    'number_of_children' => $order->number_of_children,
                    'special_requirements' => $order->special_requirements,
                    'payment' => $order->payment,
                    'emergency_contact' => $order->emergencyContact,
                    'item' => [
                        'name' => $snapshot['name'] ?? null,
                        'slug' => $snapshot['slug'] ?? null,
                        'item_type' => $snapshot['item_type'] ?? null,
                        'city' => $cityName,
                        'region' => $region?->name,
                        'locations' => $snapshot['locations'] ?? null,
                        'media' => $media,
                    ],
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $userProfile?->phone,
                    ],
                ];
            // }

            // ✅ If orderable is available (not deleted)
        //     $itemName = $orderable?->name ?? null;
        //     $itemSlug = $orderable?->slug ?? null;
        //     $itemType = $orderable?->item_type ?? class_basename($order->orderable_type);

        //     if ($orderable instanceof \App\Models\Activity) {
        //         $primaryLocation = $orderable->locations->where('location_type', 'primary')->first();
        //         $city = $primaryLocation?->city;
        //     } else {
        //         $location = $orderable->locations->first();
        //         $city = $location?->city;
        //     }

        //     $state = $city?->state;
        //     $country = $state?->country;
        //     $region = \App\Models\Region::whereHas('countries', fn ($q) => $q->where('countries.id', $country?->id))->first();

        //     $cityName = $city?->name;
        //     $regionName = $region?->name;

        //     $medias = $orderable->mediaGallery->map(function ($mediaLink) {
        //         return [
        //             'name' => $mediaLink->media?->name,
        //             'alt_text' => $mediaLink->media?->alt_text,
        //             'url' => $mediaLink->media?->url,
        //         ];
        //     });

        //     return [
        //         'id' => $order->id,
        //         'item_id' => $order->orderable_id,
        //         'status' => $order->status,
        //         'travel_date' => $order->travel_date,
        //         'preferred_time' => $order->preferred_time,
        //         'number_of_adults' => $order->number_of_adults,
        //         'number_of_children' => $order->number_of_children,
        //         'special_requirements' => $order->special_requirements,
        //         'payment' => $order->payment,
        //         'emergency_contact' => $order->emergencyContact,
        //         'item' => [
        //             'name' => $itemName,
        //             'slug' => $itemSlug,
        //             'item_type' => $itemType,
        //             'city' => $cityName,
        //             'region' => $regionName,
        //             'media' => $medias,
        //         ],
        //         'user' => [
        //             'name' => $user->name,
        //             'email' => $user->email,
        //             'phone' => $userProfile?->phone,
        //         ],
        //     ];
        });

        return response()->json([
            'success' => true,
            'orders' => $transformed->values()
        ]);
    }

    // public function getUserOrders(Request $request)
    // {
    //     $user = auth()->user();

    //     $orders = Order::with([
    //         'payment',
    //         'emergencyContact',
    //         'orderable' => function ($morphTo) {
    //             $morphTo->morphWith([
    //                 \App\Models\Activity::class => [
    //                     'locations.city.state.country',
    //                     'mediaGallery.media',
    //                 ],
    //                 \App\Models\Package::class => [
    //                     'locations.city.state.country',
    //                     'mediaGallery.media',
    //                 ],
    //                 \App\Models\Itinerary::class => [
    //                     'locations.city.state.country',
    //                     'mediaGallery.media',
    //                 ],
    //             ]);
    //         }
    //     ])->where('user_id', auth()->id())->latest()->get();

    //     if ($orders->isEmpty()) {
    //         return response()->json(['error' => 'No orders found'], 404);
    //     }

    //     $userProfile = $user->profile; // Assuming relation: User -> profile (hasOne)

    //     $transformed = $orders->map(function ($order) use ($user, $userProfile) {
    //         $orderable = $order->orderable;
    //         $itemName = $orderable?->name ?? null;
    //         $itemSlug = $orderable?->slug ?? null;
    //         $itemType = $orderable?->item_type ?? null;
    //         $cityName = null;
    //         $regionName = null;
    //         if (!$orderable) return null;
    //         if ($orderable instanceof \App\Models\Activity) {
    //             $primaryLocation = $orderable->locations
    //                 ->where('location_type', 'primary')
    //                 ->first();

    //             $city = $primaryLocation?->city;
    //             $state = $city?->state;
    //             $country = $state?->country;
    //             $countryId = $country?->id;

    //             $region = \App\Models\Region::whereHas('countries', function ($q) use ($countryId) {
    //                 $q->where('countries.id', $countryId);
    //             })->first();
    //         } else {
    //             $location = $orderable->locations->first();
    //             $city = $location?->city;
    //             $state = $city?->state;
    //             $country = $state?->country;

    //             $region = \App\Models\Region::whereHas('countries', fn ($q) => $q->where('countries.id', $country?->id))->first();
    //         }

    //         $cityName = $city?->name;
    //         $regionName = $region?->name;

    //         $medias = $orderable->mediaGallery->map(function ($mediaLink) {
    //             return [
    //                 'name' => $mediaLink->media?->name,
    //                 'alt_text' => $mediaLink->media?->alt_text,
    //                 'url' => $mediaLink->media?->url,
    //             ];
    //         });

    //         return [
    //             'id' => $order->id,
    //             'item_id' => $order->orderable_id,
    //             'status' => $order->status,
    //             'travel_date' => $order->travel_date,
    //             'preferred_time' => $order->preferred_time,
    //             'number_of_adults' => $order->number_of_adults,
    //             'number_of_children' => $order->number_of_children,
    //             'special_requirements' => $order->special_requirements,
    //             'payment' => $order->payment,
    //             'emergency_contact' => $order->emergencyContact,
    //             'item' => [
    //                 'name' => $itemName,
    //                 'slug' => $itemSlug,
    //                 'item_type' => $itemType,
    //                 'city' => $cityName,
    //                 'region' => $regionName,
    //                 'media' => $medias,
    //             ],
    //             'user' => [
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'phone' => $userProfile?->phone,
    //             ],
    //         ];
    //     })->filter()->values();

    //     return response()->json([
    //         'success' => true,
    //         'orders' => $transformed
    //     ]);
    // }

}
