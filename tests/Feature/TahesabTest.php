<?php

namespace Tests\Feature;

use App\Services\Api\Tahesab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TahesabTest extends TestCase
{
    /**
     * Test that all responses from getEtikets are arrays.
     */
    public function test_all_responses_are_array(): void
    {
        $tahesab = new Tahesab();
        $GetEtiketTableInfo = $tahesab->GetEtiketTableInfo();
        $failedRanges = [];

        if (!isset($GetEtiketTableInfo['error']) &&
            $GetEtiketTableInfo['CountALL'] &&
            $GetEtiketTableInfo['MinCode'] &&
            $GetEtiketTableInfo['MaxCode']) {

            $minCode = $GetEtiketTableInfo['MinCode'];
            $maxCode = $GetEtiketTableInfo['MaxCode'];
            $currentMax = 500;

            echo "Starting to fetch etikets from code $minCode to $maxCode\n";

            while ($minCode < $maxCode) {
                echo "Fetching etikets from $minCode to $currentMax\n";
                $result = $tahesab->getEtikets($minCode, $currentMax);

                if (!is_array($result)) {
                    $failedRanges[] = "Range $minCode to $currentMax";
                } else {
                    echo "Result etikets from $minCode to $currentMax is an array\n";
                }

                $minCode = $currentMax;
                $currentMax = $currentMax + 500;

                if ($currentMax > $maxCode) {
                    $currentMax = $maxCode;
                }
            }

            if (!empty($failedRanges)) {
                $this->fail("Non-array responses detected in the following ranges: " . implode(', ', $failedRanges));
            }

            echo "Finished fetching all etikets.\n";
        } else {
            $this->fail("Etiket table info fetch failed or missing required data.");
        }
    }
}