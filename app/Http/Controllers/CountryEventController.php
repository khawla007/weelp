<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountryEvent;
use Illuminate\Http\Request;

class CountryEventController extends Controller
{
    // Get all events for a country
    public function index($id)
    {
        $events = CountryEvent::where('country_id', $id)->get();
        return response()->json($events);
    }

    // Create or update an event
    public function store(Request $request, $id)
    {
        
        $validatedData = $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'date_time' => 'required|date',
            'location' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $validatedData['country_id'] = $id;

        $event = CountryEvent::updateOrCreate(
            ['country_id' => $id, 'name' => $validatedData['name']],
            $validatedData
        );
        return response()->json([
            'message' => 'Event stored successfully!',
            'data' => $event
        ], 201);
    }

    // Get single event details
    public function show($id, $eventId)
    {
        $event = CountryEvent::where('country_id', $id)->findOrFail($eventId);
        return response()->json($event);
    }

    // Update an event (only provided fields)
    public function update(Request $request, $id, $eventId)
    {
        $event = CountryEvent::where('country_id', $id)->findOrFail($eventId);

        $request->validate([
            'name' => 'sometimes|string',
            'type' => 'sometimes|string',
            'date_time' => 'sometimes|date',
            'location' => 'sometimes|string',
            'description' => 'sometimes|string',
        ]);

        $event->update($request->only(array_keys($request->all())));

        return response()->json([
            'message' => 'Event updated successfully!',
            'data' => $event
        ]);
    }

    // Delete an event
    public function destroy($id, $eventId)
    {
        $event = CountryEvent::where('country_id', $id)->findOrFail($eventId);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully!']);
    }
}
