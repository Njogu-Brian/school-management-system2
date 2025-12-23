export interface FeeStructure {
    id: number;
    name: string;
    class_id?: number;
    class_name?: string;
    academic_year_id: number;
    term_id?: number;
    items: FeeStructureItem[];
    total_amount: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface FeeStructureItem {
    id: number;
    votehead_id: number;
    votehead_name?: string;
    amount: number;
    is_compulsory: boolean;
}

export interface Discount {
    id: number;
    name: string;
    type: 'percentage' | 'fixed';
    value: number;
    applicable_to: 'all' | 'specific';
    classes?: number[];
    students?: number[];
    is_active: boolean;
    start_date?: string;
    end_date?: string;
}

export interface PaymentPlan {
    id: number;
    student_id: number;
    student_name?: string;
    total_amount: number;
    installments: Installment[];
    status: 'active' | 'completed' | 'defaulted';
    created_at: string;
}

export interface Installment {
    id: number;
    amount: number;
    due_date: string;
    paid_amount: number;
    status: 'pending' | 'partial' | 'paid' | 'overdue';
    paid_date?: string;
}

export interface Receipt {
    id: number;
    receipt_number: string;
    payment_id: number;
    student_id: number;
    student_name?: string;
    amount: number;
    payment_method: string;
    date: string;
    description?: string;
    received_by: string;
    created_at: string;
}

export interface StudentStatement {
    student_id: number;
    student_name: string;
    class_name: string;
    period_from: string;
    period_to: string;
    opening_balance: number;
    total_invoiced: number;
    total_paid: number;
    closing_balance: number;
    transactions: StatementTransaction[];
}

export interface StatementTransaction {
    date: string;
    type: 'invoice' | 'payment' | 'adjustment';
    reference: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
}

export interface Votehead {
    id: number;
    name: string;
    code?: string;
    category: 'tuition' | 'transport' | 'boarding' | 'other';
    is_active: boolean;
}

export interface FeeConcession {
    id: number;
    student_id: number;
    student_name?: string;
    type: 'full' | 'partial';
    percentage?: number;
    amount?: number;
    reason: string;
    approved_by?: number;
    approved_by_name?: string;
    start_date: string;
    end_date?: string;
    status: 'pending' | 'approved' | 'rejected';
}
