<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\User;

class AssignTeachersController extends Controller
{
    public function index()
    {
        // Get classrooms
        $classrooms = Classroom::with('teachers')->get();
        
        // Load streams separately grouped by classroom_id (using direct relationship)
        $streamsByClassroom = Stream::with('teachers', 'classroom')->get()->groupBy('classroom_id');
        
        // Attach streams to classrooms
        foreach ($classrooms as $classroom) {
            $classroom->streams = $streamsByClassroom->get($classroom->id, collect());
        }
        
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        
        return view('academics.assign_teachers', compact('classrooms', 'teachers'));
    }
}
