<?php

namespace App\Services;

use App\Models\CategoryCompatibility;

class CategoryCompatibilityService
{
    public function checkCompatibility($component1, $component2)
    {
        $type1 = $component1->component_type_id;
        $type2 = $component2->component_type_id;

        return CategoryCompatibility::areCompatible($type1, $type2);
    }
}
