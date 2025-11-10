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
        $classrooms = Classroom::with('teachers')->get();
        $streams = Stream::with('teachers')->get();
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        
        return view('academics.assign_teachers', compact('classrooms', 'streams', 'teachers'));
    }
}
