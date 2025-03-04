<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Country;
use App\Models\State;
use App\Models\StateLocationDetail;
use App\Models\StateTravelInfo;
use App\Models\StateSeason;
use App\Models\StateEvent;
use App\Models\StateAdditionalInfo;
use App\Models\StateFaq;
use App\Models\StateSeo;

class StateImportController extends Controller
{
    public function import(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|url',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        $fileUrl = $request->input('file');
    
        $response = Http::get($fileUrl);
    
        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to download the file.'], 400);
        }
    
        $tempFilePath = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFilePath, $response->body());
    
        $file = fopen($tempFilePath, 'r');
        $header = fgetcsv($file); 
    
        while ($row = fgetcsv($file)) {
           
            if (count($row) < 46) { 
                continue;
            }
    
            if ($this->isRowMalformed($row)) {
                continue;
            }
            
            // Finding country_id from the countries table by sheet's country code/country name
            $country = Country::where('name', $this->sanitizeInput($row[3]))
            ->orWhere('country_code', $this->sanitizeInput($row[3]))
            ->first();

            if (!$country) {
                return response()->json(['message' => 'Country ID not exist!'], 404);
            }

            $state = State::create([
            'name' => $this->sanitizeInput($row[0]),
            'state_code' => $this->sanitizeInput($row[1]),
            'slug' => $this->sanitizeInput($row[2]),
            'country_id' => $country->id, 
            'description' => $this->sanitizeInput($row[4]),
            'feature_image' => $this->sanitizeInput($row[5]),
            'featured_destination' => filter_var($this->sanitizeInput($row[6]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            ]);
    
            StateLocationDetail::create([
                'state_id' => $state->id,
                'latitude' => $this->sanitizeInput($row[7]),
                'longitude' => $this->sanitizeInput($row[8]),
                'capital_city' => $this->sanitizeInput($row[9]),
                'population' => $this->sanitizeInput($row[10]),
                'currency' => $this->sanitizeInput($row[11]),
                'timezone' => $this->sanitizeInput($row[12]),
                'language' => $this->sanitizeInput($row[13]),
                'local_cuisine' => $this->sanitizeInput($row[14]),
            ]);
    
            StateTravelInfo::create([
                'state_id' => $state->id,
                'airport' => $this->sanitizeInput($row[15]),
                'public_transportation' => $this->sanitizeInput($row[16]),

                // 'taxi_available' => ($this->sanitizeInput($row[17])) === 'Yes',
                // 'rental_cars_available' => ($this->sanitizeInput($row[18])) === 'Yes',
                // 'hotels' => ($this->sanitizeInput($row[19])) === 'Yes',
                // 'hostels' => ($this->sanitizeInput($row[20])) === 'Yes',
                // 'apartments' => ($this->sanitizeInput($row[21])) === 'Yes',
                // 'resorts' => ($this->sanitizeInput($row[22])) === 'Yes',

                'taxi_available' => filter_var($this->sanitizeInput($row[17]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'rental_cars_available' => filter_var($this->sanitizeInput($row[18]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'hotels' => filter_var($this->sanitizeInput($row[19]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'hostels' => filter_var($this->sanitizeInput($row[20]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'apartments' => filter_var($this->sanitizeInput($row[21]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'resorts' => filter_var($this->sanitizeInput($row[22]), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                

                'visa_requirements' => $this->sanitizeInput($row[23]),
                'best_time_to_visit' => $this->sanitizeInput($row[24]),
                'travel_tips' => $this->sanitizeInput($row[25]),
                'safety_information' => $this->sanitizeInput($row[26]),
            ]);
    
            StateSeason::create([
                'state_id' => $state->id,
                'name' => $this->sanitizeInput($row[27]),
                'months' => $this->sanitizeInput($row[28]),
                'weather' => $this->sanitizeInput($row[29]),
                'activities' => $this->sanitizeInput($row[30]),
            ]);
    
            StateEvent::create([
                'state_id' => $state->id,
                'name' => $this->sanitizeInput($row[31]),
                'type' => $this->sanitizeInput($row[32]),
                'date_time' => $this->sanitizeInput($row[33]),
                'location' => $this->sanitizeInput($row[34]),
                'description' => $this->sanitizeInput($row[35]),
            ]);
    
            StateAdditionalInfo::create([
                'state_id' => $state->id,
                'title' => $this->sanitizeInput($row[36]),
                'content' => $this->sanitizeInput($row[37]),
            ]);
    
            StateFaq::create([
                'state_id' => $state->id,
                'question' => $this->sanitizeInput($row[38]),
                'answer' => $this->sanitizeInput($row[39]),
            ]);
    
            StateSeo::create([
                'state_id' => $state->id,
                'meta_title' => $this->sanitizeInput($row[40]),
                'meta_description' => $this->sanitizeInput($row[41]),
                'keywords' => $this->sanitizeInput($row[42]),
                'og_image_url' => $this->sanitizeInput($row[43]),
                'canonical_url' => $this->sanitizeInput($row[44]),
                'schema_type' => $this->sanitizeInput($row[45]),
                'schema_data' => json_decode($this->sanitizeInput($row[46]), true),
            ]);
        }
    
        fclose($file);
    
        // Delete the temporary file
        unlink($tempFilePath);
    
        return response()->json(['message' => 'State imported successfully!'], 200);
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