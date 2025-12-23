export interface Book {
    id: number;
    title: string;
    isbn?: string;
    author: string;
    publisher?: string;
    publication_year?: number;
    category: string;
    language?: string;
    total_copies: number;
    available_copies: number;
    location?: string;
    description?: string;
    cover_image?: string;
    status: 'available' | 'limited' | 'unavailable';
    created_at: string;
    updated_at: string;
}

export interface LibraryCard {
    id: number;
    card_number: string;
    holder_type: 'student' | 'staff';
    holder_id: number;
    holder_name?: string;
    issue_date: string;
    expiry_date: string;
    status: 'active' | 'expired' | 'suspended';
    max_books_allowed: number;
    current_books_borrowed: number;
    created_at: string;
    updated_at: string;
}

export interface Borrowing {
    id: number;
    book_id: number;
    book_title?: string;
    card_id: number;
    card_number?: string;
    borrower_name?: string;
    borrow_date: string;
    due_date: string;
    return_date?: string;
    status: 'active' | 'returned' | 'overdue' | 'lost';
    fine_amount?: number;
    fine_paid?: boolean;
    notes?: string;
    created_at: string;
    updated_at: string;
}

export interface LibraryFilters {
    search?: string;
    category?: string;
    author?: string;
    status?: string;
    borrower_id?: number;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
}
