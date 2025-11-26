<?php

namespace App\Services;

use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelAllocation;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class HostelService
{
    /**
     * Allocate student to hostel room
     */
    public function allocateStudent(
        Student $student,
        HostelRoom $room,
        ?string $bedNumber = null
    ): HostelAllocation {
        // Check if student already has active allocation
        $existing = HostelAllocation::where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            throw new \Exception('Student already has an active hostel allocation');
        }

        // Validate room availability
        if (!$room->hasAvailableSpace()) {
            throw new \Exception('Room is full or not available');
        }

        // Validate hostel type matches student gender
        if ($room->hostel->type !== 'mixed') {
            $expectedType = $student->gender === 'male' ? 'boys' : 'girls';
            if ($room->hostel->type !== $expectedType) {
                throw new \Exception('Hostel type does not match student gender');
            }
        }

        return DB::transaction(function () use ($student, $room, $bedNumber) {
            // Create allocation
            $allocation = HostelAllocation::create([
                'student_id' => $student->id,
                'hostel_id' => $room->hostel_id,
                'room_id' => $room->id,
                'bed_number' => $bedNumber,
                'allocation_date' => now(),
                'status' => 'active',
                'allocated_by' => auth()->id(),
            ]);

            // Update room occupancy
            $room->increment('current_occupancy');

            // Update hostel occupancy
            $room->hostel->increment('current_occupancy');

            // Update room status if full
            if ($room->current_occupancy >= $room->capacity) {
                $room->update(['status' => 'occupied']);
            }

            return $allocation;
        });
    }

    /**
     * Deallocate student from hostel
     */
    public function deallocateStudent(HostelAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            // Update allocation
            $allocation->update([
                'deallocation_date' => now(),
                'status' => 'completed',
            ]);

            // Update room occupancy
            $room = $allocation->room;
            $room->decrement('current_occupancy');
            $room->update(['status' => 'available']);

            // Update hostel occupancy
            $allocation->hostel->decrement('current_occupancy');
        });
    }

    /**
     * Get available rooms in hostel
     */
    public function getAvailableRooms(Hostel $hostel): \Illuminate\Database\Eloquent\Collection
    {
        return $hostel->rooms()
            ->where('status', 'available')
            ->whereColumn('current_occupancy', '<', 'capacity')
            ->get();
    }
}

