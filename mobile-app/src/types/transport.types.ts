export interface Vehicle {
    id: number;
    registration_number: string;
    make: string;
    model: string;
    year?: number;
    capacity: number;
    status: 'active' | 'maintenance' | 'inactive';
    driver_id?: number;
    driver_name?: string;
    insurance_expiry?: string;
    last_service_date?: string;
    next_service_date?: string;
    created_at: string;
    updated_at: string;
}

export interface Route {
    id: number;
    name: string;
    code?: string;
    description?: string;
    vehicle_id?: number;
    vehicle_registration?: string;
    driver_id?: number;
    driver_name?: string;
    fee_amount?: number;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
    drop_points?: DropPoint[];
    students_count?: number;
}

export interface DropPoint {
    id: number;
    route_id: number;
    name: string;
    location: string;
    sequence: number;
    pickup_time?: string;
    dropoff_time?: string;
    students_count?: number;
}

export interface Trip {
    id: number;
    route_id: number;
    route_name?: string;
    vehicle_id: number;
    vehicle_registration?: string;
    driver_id: number;
    driver_name?: string;
    date: string;
    type: 'pickup' | 'dropoff';
    start_time?: string;
    end_time?: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    students_picked?: number;
    students_dropped?: number;
    notes?: string;
    created_at: string;
    updated_at: string;
}

export interface StudentRouteAssignment {
    id: number;
    student_id: number;
    student_name?: string;
    student_admission_number?: string;
    route_id: number;
    route_name?: string;
    drop_point_id?: number;
    drop_point_name?: string;
    status: 'active' | 'inactive';
    start_date: string;
    end_date?: string;
}

export interface TransportFilters {
    search?: string;
    status?: string;
    route_id?: number;
    driver_id?: number;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
}
