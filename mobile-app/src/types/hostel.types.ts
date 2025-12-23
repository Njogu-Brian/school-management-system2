export interface Hostel {
    id: number;
    name: string;
    code?: string;
    type: 'boys' | 'girls' | 'mixed';
    capacity: number;
    occupied: number;
    available: number;
    warden_id?: number;
    warden_name?: string;
    description?: string;
    status: 'active' | 'inactive' | 'maintenance';
    created_at: string;
    updated_at: string;
    rooms?: Room[];
}

export interface Room {
    id: number;
    hostel_id: number;
    hostel_name?: string;
    room_number: string;
    floor?: number;
    type: 'single' | 'double' | 'triple' | 'quad' | 'dormitory';
    capacity: number;
    occupied: number;
    available: number;
    status: 'available' | 'full' | 'maintenance';
    facilities?: string[];
    created_at: string;
    updated_at: string;
}

export interface RoomAllocation {
    id: number;
    student_id: number;
    student_name?: string;
    student_admission_number?: string;
    hostel_id: number;
    hostel_name?: string;
    room_id: number;
    room_number?: string;
    bed_number?: string;
    allocation_date: string;
    deallocation_date?: string;
    status: 'active' | 'inactive';
    academic_year_id?: number;
    term_id?: number;
    created_at: string;
    updated_at: string;
}

export interface HostelFilters {
    search?: string;
    type?: string;
    status?: string;
    hostel_id?: number;
    page?: number;
    per_page?: number;
}
