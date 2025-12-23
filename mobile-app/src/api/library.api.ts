import { apiClient } from './client';
import { Book, LibraryCard, Borrowing, LibraryFilters } from '../types/library.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const libraryApi = {
    // ========== Books ==========
    async getBooks(filters?: LibraryFilters): Promise<ApiResponse<PaginatedResponse<Book>>> {
        return apiClient.get<PaginatedResponse<Book>>('/library/books', filters);
    },

    async getBook(id: number): Promise<ApiResponse<Book>> {
        return apiClient.get<Book>(`/library/books/${id}`);
    },

    async createBook(data: any): Promise<ApiResponse<Book>> {
        return apiClient.post<Book>('/library/books', data);
    },

    async updateBook(id: number, data: any): Promise<ApiResponse<Book>> {
        return apiClient.put<Book>(`/library/books/${id}`, data);
    },

    async deleteBook(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/library/books/${id}`);
    },

    // ========== Library Cards ==========
    async getLibraryCards(filters?: { holder_type?: string; status?: string }): Promise<ApiResponse<PaginatedResponse<LibraryCard>>> {
        return apiClient.get<PaginatedResponse<LibraryCard>>('/library/cards', filters);
    },

    async getLibraryCard(id: number): Promise<ApiResponse<LibraryCard>> {
        return apiClient.get<LibraryCard>(`/library/cards/${id}`);
    },

    async createLibraryCard(data: any): Promise<ApiResponse<LibraryCard>> {
        return apiClient.post<LibraryCard>('/library/cards', data);
    },

    async suspendCard(id: number, reason: string): Promise<ApiResponse<LibraryCard>> {
        return apiClient.post<LibraryCard>(`/library/cards/${id}/suspend`, { reason });
    },

    async activateCard(id: number): Promise<ApiResponse<LibraryCard>> {
        return apiClient.post<LibraryCard>(`/library/cards/${id}/activate`);
    },

    // ========== Borrowings ==========
    async getBorrowings(filters?: LibraryFilters): Promise<ApiResponse<PaginatedResponse<Borrowing>>> {
        return apiClient.get<PaginatedResponse<Borrowing>>('/library/borrowings', filters);
    },

    async getBorrowing(id: number): Promise<ApiResponse<Borrowing>> {
        return apiClient.get<Borrowing>(`/library/borrowings/${id}`);
    },

    async borrowBook(data: { book_id: number; card_id: number; due_date: string }): Promise<ApiResponse<Borrowing>> {
        return apiClient.post<Borrowing>('/library/borrowings', data);
    },

    async returnBook(id: number): Promise<ApiResponse<Borrowing>> {
        return apiClient.post<Borrowing>(`/library/borrowings/${id}/return`);
    },

    async renewBook(id: number, new_due_date: string): Promise<ApiResponse<Borrowing>> {
        return apiClient.post<Borrowing>(`/library/borrowings/${id}/renew`, { new_due_date });
    },

    async markAsLost(id: number): Promise<ApiResponse<Borrowing>> {
        return apiClient.post<Borrowing>(`/library/borrowings/${id}/mark-lost`);
    },

    // ========== Reports ==========
    async getLibrarySummary(): Promise<ApiResponse<{
        total_books: number;
        available_books: number;
        borrowed_books: number;
        overdue_books: number;
        active_cards: number;
    }>> {
        return apiClient.get('/library/summary');
    },
};
