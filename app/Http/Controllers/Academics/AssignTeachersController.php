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
        // Get classrooms with their streams and teachers
        $classrooms = Classroom::with(['streams.teachers', 'teachers'])->get();
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        
        return view('academics.assign_teachers', compact('classrooms', 'teachers'));
    }
}
