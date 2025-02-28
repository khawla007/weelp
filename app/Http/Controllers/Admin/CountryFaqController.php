<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryFaq;
use Illuminate\Http\Request;

class CountryFaqController extends Controller
{
    // Get all FAQs for a country
    public function index($id)
    {
        $faqs = CountryFaq::where('country_id', $id)->orderBy('question_number')->get();
        return response()->json($faqs);
    }

    // Create a new FAQ
    public function store(Request $request, $id)
    {
        $validatedData = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $lastFaq = CountryFaq::where('country_id', $id)->orderBy('question_number', 'desc')->first();
        $questionNumber = $lastFaq ? $lastFaq->question_number + 1 : 1;

        $faq = CountryFaq::create([
            'country_id' => $id,
            'question_number' => $questionNumber,
            'question' => $validatedData['question'],
            'answer' => $validatedData['answer'],
        ]);

        return response()->json([
            'message' => 'FAQ added successfully!',
            'data' => $faq
        ], 201);
    }

    // Get a single FAQ
    public function show($id, $faqId)
    {
        $faq = CountryFaq::where('country_id', $id)->findOrFail($faqId);
        return response()->json($faq);
    }

    // Update an FAQ
    public function update(Request $request, $id, $faqId)
    {
        $faq = CountryFaq::where('country_id', $id)->findOrFail($faqId);

        $validatedData = $request->validate([
            'question' => 'sometimes|string',
            'answer' => 'sometimes|string',
        ]);

        $faq->update($validatedData);

        return response()->json([
            'message' => 'FAQ updated successfully!',
            'data' => $faq
        ]);
    }

    // Delete an FAQ
    public function destroy($id, $faqId)
    {
        $faq = CountryFaq::where('country_id', $id)->findOrFail($faqId);
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted successfully!']);
    }
}
