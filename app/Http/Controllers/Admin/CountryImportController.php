<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\CountryLocationDetail;
use App\Models\CountryTravelInfo;
use App\Models\CountrySeason;
use App\Models\CountryEvent;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryFaq;
use App\Models\CountrySeo;

class CountryImportController extends Controller
{
    public function import(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|url',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        // Get the file URL from the request
        $fileUrl = $request->input('file');
    
        // Download the file
        $response = Http::get($fileUrl);
    
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to download the file.'], 400);
        }
    
        // Save the file temporarily
        $tempFilePath = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFilePath, $response->body());
    
        // Process the file
        $file = fopen($tempFilePath, 'r');
        $header = fgetcsv($file); // Skip the header row
    
        while ($row = fgetcsv($file)) {
            // Skip rows with insufficient columns
            if (count($row) < 46) { // Ensure there are at least 46 columns
                continue;
            }
    
            // Skip rows with malformed data (e.g., JavaScript or HTML)
            if ($this->isRowMalformed($row)) {
                continue;
            }
    
            // Insert into the `countries` table
            $country = Country::create([
                'name' => $this->sanitizeInput($row[0]),
                'country_code' => $this->sanitizeInput($row[1]),
                'slug' => $this->sanitizeInput($row[2]),
                'description' => $this->sanitizeInput($row[3]),
                'feature_image' => $this->sanitizeInput($row[4]),
                'featured_destination' => ($this->sanitizeInput($row[5])) === 'Yes',
            ]);
    
            // Insert into the `country_details` table
            CountryLocationDetail::create([
                'country_id' => $country->id,
                'latitude' => $this->sanitizeInput($row[6]),
                'longitude' => $this->sanitizeInput($row[7]),
                'capital_city' => $this->sanitizeInput($row[8]),
                'population' => $this->sanitizeInput($row[9]),
                'currency' => $this->sanitizeInput($row[10]),
                'timezone' => $this->sanitizeInput($row[11]),
                'language' => $this->sanitizeInput($row[12]),
                'local_cuisine' => $this->sanitizeInput($row[13]),
            ]);
    
            // Insert into the `country_travel_info` table
            CountryTravelInfo::create([
                'country_id' => $country->id,
                'airport' => $this->sanitizeInput($row[14]),
                'public_transportation' => $this->sanitizeInput($row[15]),
                'taxi_available' => ($this->sanitizeInput($row[16])) === 'Yes',
                'rental_cars_available' => ($this->sanitizeInput($row[17])) === 'Yes',
                'hotels' => ($this->sanitizeInput($row[18])) === 'Yes',
                'hostels' => ($this->sanitizeInput($row[19])) === 'Yes',
                'apartments' => ($this->sanitizeInput($row[20])) === 'Yes',
                'resorts' => ($this->sanitizeInput($row[21])) === 'Yes',
                'visa_requirements' => $this->sanitizeInput($row[22]),
                'best_time_to_visit' => $this->sanitizeInput($row[23]),
                'travel_tips' => $this->sanitizeInput($row[24]),
                'safety_information' => $this->sanitizeInput($row[25]),
            ]);
    
            // Insert into the `country_seasons` table
            CountrySeason::create([
                'country_id' => $country->id,
                'name' => $this->sanitizeInput($row[26]),
                'months' => $this->sanitizeInput($row[27]),
                'weather' => $this->sanitizeInput($row[28]),
                'activities' => $this->sanitizeInput($row[29]),
            ]);
    
            // Insert into the `country_events` table
            CountryEvent::create([
                'country_id' => $country->id,
                'name' => $this->sanitizeInput($row[30]),
                'type' => $this->sanitizeInput($row[31]),
                'date_time' => $this->sanitizeInput($row[32]),
                'location' => $this->sanitizeInput($row[33]),
                'description' => $this->sanitizeInput($row[34]),
            ]);
    
            // Insert into the `country_additional_info` table
            CountryAdditionalInfo::create([
                'country_id' => $country->id,
                'title' => $this->sanitizeInput($row[35]),
                'content' => $this->sanitizeInput($row[36]),
            ]);
    
            // Insert into the `country_faqs` table
            CountryFaq::create([
                'country_id' => $country->id,
                'question' => $this->sanitizeInput($row[37]),
                'answer' => $this->sanitizeInput($row[38]),
            ]);
    
            // Insert into the `country_seo` table
            CountrySeo::create([
                'country_id' => $country->id,
                'meta_title' => $this->sanitizeInput($row[39]),
                'meta_description' => $this->sanitizeInput($row[40]),
                'keywords' => $this->sanitizeInput($row[41]),
                'og_image_url' => $this->sanitizeInput($row[42]),
                'canonical_url' => $this->sanitizeInput($row[43]),
                'schema_type' => $this->sanitizeInput($row[44]),
                'schema_data' => $this->sanitizeInput($row[45]),
            ]);
        }
    
        fclose($file);
    
        // Delete the temporary file
        unlink($tempFilePath);
    
        return response()->json(['message' => 'Countries imported successfully!'], 200);
    }
    
    /**
     * Check if a row contains malformed data (e.g., JavaScript or HTML).
     */
    private function isRowMalformed(array $row): bool
    {
        foreach ($row as $value) {
            if (strpos($value, '<script>') !== false || strpos($value, 'function(') !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Sanitize input data by removing HTML tags and trimming whitespace.
     */
    private function sanitizeInput(?string $value): ?string
    {
        return $value ? trim(strip_tags($value)) : null;
    }
}