# Vehicle Number Format Update

## Summary

The transport import system now accepts **both short vehicle codes and full registration numbers**.

## What Changed

### Before
- Excel required: `KDR TRIP 1`, `KCB TRIP 2`
- Only 3-letter codes accepted

### After
- Excel accepts: 
  - `KDR TRIP 1` ✅ (short code)
  - `KDR936F TRIP 1` ✅ (full registration)
  - `KAQ967W TRIP 2` ✅ (full registration)
  - `KCB334B TRIP 1` ✅ (full registration)

## How It Works

1. **Extraction**: System extracts first 3 letters from vehicle column
   - `KDR936F` → `KDR`
   - `KAQ967W` → `KAQ`
   - `KCB334B` → `KCB`

2. **Matching**: Searches for vehicles starting with those 3 letters
   - Finds vehicles like `KDR`, `KDR936F`, or any vehicle starting with `KDR`

3. **Fallback**: If no match found, tries exact match as fallback

## Examples

### Excel Format (All Valid)

| ADMISSION NO | NAME | ROUTE | CLASS | VEHICLE |
|--------------|------|-------|-------|---------|
| RKS438 | John Doe | REGEN | FOUNDATION LOVE | **KDR936F TRIP 1** |
| RKS644 | Jane Smith | RUKUBI | FOUNDATION PEACE | **KCB334B TRIP 2** |
| RKS582 | Alice Brown | MUTHURE | FOUNDATION LOVE | **KAQ967W TRIP 1** |
| RKS789 | Bob Wilson | MUTHUMU | FOUNDATION PEACE | **KDR TRIP 1** |
| RKS456 | Own Transport | OWN | FOUNDATION LOVE | **OWN** |

### Trip Names Created

The system creates trips using the **3-letter code** (not full registration):
- `KDR TRIP 1` (from `KDR936F TRIP 1`)
- `KCB TRIP 2` (from `KCB334B TRIP 2`)
- `KAQ TRIP 1` (from `KAQ967W TRIP 1`)

This ensures consistency in the database.

## Benefits

1. **Flexibility**: Users can copy full registration numbers from their Excel sheets
2. **Accuracy**: Reduces typing errors
3. **Convenience**: No need to manually extract 3-letter codes
4. **Backward Compatible**: Short codes still work

## User-Facing Changes

### Import Form
- Updated instructions to show both formats
- Added info note about automatic matching
- Updated examples in warnings

### Template Download
- Now includes full registration number examples
- Shows variety of formats

## Technical Implementation

**File**: `app/Imports/TransportAssignmentImport.php`

```php
// Extract first 3 letters from vehicle number
$vehicleNumber = strtoupper(substr($vehicleNumberRaw, 0, 3));

// Find vehicle by matching the first 3 characters
$vehicle = Vehicle::where('vehicle_number', 'LIKE', $vehicleNumber . '%')->first();

// Fallback to exact match
if (!$vehicle) {
    $vehicle = Vehicle::where('vehicle_number', $vehicleNumberRaw)->first();
}
```

## Error Messages

Updated error message to be more helpful:
```
Vehicle starting with 'KDR' (from 'KDR936F') not found. Please create it first.
```

---

**Date**: January 7, 2026  
**Status**: ✅ Complete and Tested

