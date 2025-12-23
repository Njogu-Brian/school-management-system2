export interface FeeStructure {
    id: number;
    name: string;
    class_id: number;
    term_id?: number;
    academic_year_id?: number;
    class_name?: string;
    total_amount: number;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
    charges?: FeeCharge[];
}

export interface FeeCharge {
    id: number;
    fee_structure_id: number;
    votehead_id: number;
    votehead_name: string;
    amount: number;
    is_mandatory: boolean;
}

export interface Votehead {
    id: number;
    name: string;
    code: string;
    type: 'tuition' | 'activity' | 'transport' | 'boarding' | 'other';
    is_active: boolean;
}

export interface Invoice {
    id: number;
    invoice_number: string;
    student_id: number;
    student_name?: string;
    student_admission_number?: string;
    term_id?: number;
    academic_year_id?: number;
    total_amount: number;
    paid_amount: number;
    balance: number;
    status: 'draft' | 'issued' | 'partially_paid' | 'paid' | 'overdue' | 'reversed';
    due_date?: string;
    issue_date: string;
    created_at: string;
    updated_at: string;
    items?: InvoiceItem[];
}

export interface InvoiceItem {
    id: number;
    invoice_id: number;
    votehead_id: number;
    votehead_name: string;
    amount: number;
    quantity: number;
    total: number;
}

export interface Payment {
    id: number;
    receipt_number: string;
    student_id: number;
    student_name?: string;
    student_admission_number?: string;
    amount: number;
    payment_method: 'cash' | 'mpesa' | 'bank_transfer' | 'cheque' | 'card' | 'stripe' | 'paypal';
    payment_date: string;
    reference_number?: string;
    notes?: string;
    status: 'pending' | 'completed' | 'failed' | 'reversed';
    created_by?: number;
    created_at: string;
    updated_at: string;
    allocations?: PaymentAllocation[];
}

export interface PaymentAllocation {
    id: number;
    payment_id: number;
    invoice_id: number;
    invoice_number: string;
    amount: number;
}

export interface StudentStatement {
    student: {
        id: number;
        full_name: string;
        admission_number: string;
        class_name: string;
    };
    opening_balance: number;
    total_invoiced: number;
    total_paid: number;
    closing_balance: number;
    transactions: StatementTransaction[];
}

export interface StatementTransaction {
    id: number;
    date: string;
    type: 'invoice' | 'payment';
    reference: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
}

export interface PaymentPlan {
    id: number;
    student_id: number;
    student_name?: string;
    total_amount: number;
    installments: number;
    frequency: 'weekly' | 'monthly' | 'termly';
    start_date: string;
    end_date: string;
    status: 'active' | 'completed' | 'defaulted';
    created_at: string;
    updated_at: string;
    schedule?: PaymentInstallment[];
}

export interface PaymentInstallment {
    id: number;
    payment_plan_id: number;
    due_date: string;
    amount: number;
    paid_amount: number;
    status: 'pending' | 'paid' | 'overdue';
}

export interface OnlinePaymentRequest {
    student_id: number;
    amount: number;
    payment_method: 'mpesa' | 'stripe' | 'paypal';
    phone_number?: string; // For M-Pesa
    email?: string; // For Stripe/PayPal
    callback_url?: string;
}

export interface OnlinePaymentResponse {
    success: boolean;
    transaction_id: string;
    checkout_url?: string; // For Stripe/PayPal
    message: string;
    status: 'pending' | 'completed' | 'failed';
}

export interface FinanceFilters {
    student_id?: number;
    class_id?: number;
    term_id?: number;
    academic_year_id?: number;
    status?: string;
    date_from?: string;
    date_to?: string;
    payment_method?: string;
    search?: string;
    page?: number;
    per_page?: number;
}
