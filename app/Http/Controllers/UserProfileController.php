<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserMeta;  // Import UserMeta model
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
                  
        if (isset($validated['urls'])) {
            
            $existingUrls = $profile->urls()->orderBy('id')->get();
        
            $incomingUrls = $validated['urls'];
            $existingCount = $existingUrls->count();
            $incomingCount = count($incomingUrls);
        
            foreach ($incomingUrls as $index => $urlData) {
                if ($index < $existingCount) {
                    $existingUrl = $existingUrls[$index];
                    $existingUrl->update($urlData);
                } else {
                    $profile->urls()->create($urlData);
                }
            }
        
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
}
