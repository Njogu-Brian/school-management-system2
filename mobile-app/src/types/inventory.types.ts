export interface InventoryItem {
    id: number;
    name: string;
    code?: string;
    category: string;
    unit: string;
    quantity: number;
    reorder_level?: number;
    unit_price?: number;
    location?: string;
    status: 'in_stock' | 'low_stock' | 'out_of_stock';
    supplier?: string;
    created_at: string;
    updated_at: string;
}

export interface StockAdjustment {
    id: number;
    item_id: number;
    item_name?: string;
    type: 'addition' | 'deduction' | 'damage' | 'lost';
    quantity: number;
    reason: string;
    adjusted_by?: number;
    adjusted_by_name?: string;
    date: string;
    created_at: string;
}

export interface Requisition {
    id: number;
    requisition_number: string;
    requested_by: number;
    requested_by_name?: string;
    department?: string;
    status: 'pending' | 'approved' | 'partially_fulfilled' | 'fulfilled' | 'rejected';
    request_date: string;
    approved_by?: number;
    approved_at?: string;
    rejection_reason?: string;
    items?: RequisitionItem[];
    created_at: string;
    updated_at: string;
}

export interface RequisitionItem {
    id: number;
    requisition_id: number;
    item_id: number;
    item_name?: string;
    quantity_requested: number;
    quantity_approved?: number;
    quantity_issued?: number;
    notes?: string;
}

export interface StudentRequirement {
    id: number;
    class_id: number;
    class_name?: string;
    term_id?: number;
    academic_year_id?: number;
    items?: RequirementItem[];
    created_at: string;
    updated_at: string;
}

export interface RequirementItem {
    id: number;
    requirement_id: number;
    item_name: string;
    quantity: number;
    unit: string;
    is_mandatory: boolean;
}

export interface InventoryFilters {
    search?: string;
    category?: string;
    status?: string;
    page?: number;
    per_page?: number;
}
