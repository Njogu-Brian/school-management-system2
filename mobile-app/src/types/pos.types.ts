export interface Product {
    id: number;
    name: string;
    sku: string;
    category: string;
    description?: string;
    price: number;
    cost_price?: number;
    stock_quantity: number;
    status: 'active' | 'inactive' | 'out_of_stock';
    has_variants: boolean;
    image?: string;
    created_at: string;
    updated_at: string;
    variants?: ProductVariant[];
}

export interface ProductVariant {
    id: number;
    product_id: number;
    name: string;
    sku: string;
    price: number;
    stock_quantity: number;
    attributes?: { [key: string]: string };
}

export interface Order {
    id: number;
    order_number: string;
    customer_id?: number;
    customer_type: 'student' | 'staff' | 'parent' | 'guest';
    customer_name?: string;
    total_amount: number;
    payment_status: 'pending' | 'paid' | 'partially_paid' | 'refunded';
    fulfillment_status: 'pending' | 'processing' | 'ready' | 'completed' | 'cancelled';
    payment_method?: string;
    order_date: string;
    notes?: string;
    items?: OrderItem[];
    created_at: string;
    updated_at: string;
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number;
    variant_id?: number;
    product_name: string;
    variant_name?: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface Uniform {
    id: number;
    name: string;
    category: 'shirt' | 'trousers' | 'skirt' | 'dress' | 'shoes' | 'tie' | 'blazer' | 'other';
    gender: 'male' | 'female' | 'unisex';
    sizes: string[];
    price: number;
    stock_by_size?: { [size: string]: number };
    image?: string;
    status: 'active' | 'inactive';
}

export interface POSFilters {
    search?: string;
    category?: string;
    status?: string;
    customer_type?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
}
