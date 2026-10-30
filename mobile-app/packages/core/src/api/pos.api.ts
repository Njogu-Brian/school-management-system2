import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface PosPaymentInstructions {
  paybill: string;
  account: string;
  contact_name: string;
  contact_whatsapp: string;
  note: string;
}

export interface PosCatalogItem {
  template_id: number;
  product_id: number;
  name: string;
  brand?: string | null;
  unit?: string | null;
  default_quantity: number;
  unit_price: number;
  line_total: number;
  is_verification_only?: boolean;
  custody_type?: string | null;
  image_url?: string | null;
  images?: string[];
}

export interface PosRequirementsCatalog {
  student: { id: number; name: string; classroom?: string | null };
  payment: PosPaymentInstructions;
  package_total: number;
  items: PosCatalogItem[];
}

export interface PosUniformVariant {
  id: number;
  variant_type: string;
  name?: string | null;
  value: string;
  stock_quantity: number;
  price_adjustment: number;
  price: number;
  is_active: boolean;
}

export interface PosUniform {
  id: number;
  name: string;
  brand?: string | null;
  base_price: number;
  cost_price?: number | null;
  stock_quantity: number;
  total_stock: number;
  track_stock: boolean;
  is_active: boolean;
  is_publicly_visible: boolean;
  image_url?: string | null;
  images?: string[];
  variants: PosUniformVariant[];
}

export interface PosOrderItem {
  id: number;
  product_id: number;
  variant_id?: number | null;
  requirement_template_id?: number | null;
  product_name: string;
  variant_name?: string | null;
  quantity: number;
  unit_price: number;
  total_price: number;
  fulfillment_status: string;
}

export interface PosOrder {
  id: number;
  order_number: string;
  order_type: string;
  status: string;
  payment_status: string;
  payment_method?: string | null;
  payment_reference?: string | null;
  subtotal: number;
  total_amount: number;
  balance: number;
  paid_at?: string | null;
  created_at?: string | null;
  student?: { id: number; name: string; classroom?: string | null } | null;
  items: PosOrderItem[];
}

export interface PosOrderCreateItem {
  product_id: number;
  quantity: number;
  variant_id?: number | null;
  requirement_template_id?: number | null;
}

export const posApi = {
  paymentInstructions(): Promise<ApiResponse<PosPaymentInstructions>> {
    return apiClient.get<PosPaymentInstructions>('/pos/payment-instructions');
  },

  requirementsCatalog(studentId: number): Promise<ApiResponse<PosRequirementsCatalog>> {
    return apiClient.get<PosRequirementsCatalog>(`/students/${studentId}/pos/requirements-catalog`);
  },

  listUniforms(params?: { student_id?: number }): Promise<ApiResponse<PosUniform[]>> {
    return apiClient.get<PosUniform[]>('/pos/uniforms', params);
  },

  upsertUniform(payload: Record<string, unknown>): Promise<ApiResponse<PosUniform>> {
    return apiClient.post<PosUniform>('/pos/uniforms', payload);
  },

  updateUniformStock(
    productId: number,
    payload: { stock_quantity?: number; variants?: Array<{ id: number; stock_quantity: number }> },
  ): Promise<ApiResponse<PosUniform>> {
    return apiClient.patch<PosUniform>(`/pos/uniforms/${productId}/stock`, payload);
  },

  createOrder(
    studentId: number,
    payload: {
      items: PosOrderCreateItem[];
      notes?: string;
      transaction_code?: string;
      payment_method?: string;
    },
    asAdmin = false,
  ): Promise<ApiResponse<PosOrder>> {
    const path = asAdmin
      ? `/admin/students/${studentId}/pos/orders`
      : `/students/${studentId}/pos/orders`;
    return apiClient.post<PosOrder>(path, payload);
  },

  confirmPayment(
    orderId: number,
    transactionCode: string,
    paymentMethod?: string,
  ): Promise<ApiResponse<PosOrder>> {
    return apiClient.post<PosOrder>(`/pos/orders/${orderId}/confirm-payment`, {
      transaction_code: transactionCode,
      payment_method: paymentMethod,
    });
  },

  listOrders(params?: {
    payment_status?: string;
    order_type?: string;
    status?: string;
    student_id?: number;
    page?: number;
  }): Promise<ApiResponse<PosOrder[]>> {
    return apiClient.get<PosOrder[]>('/pos/orders', params);
  },

  getOrder(orderId: number): Promise<ApiResponse<PosOrder>> {
    return apiClient.get<PosOrder>(`/pos/orders/${orderId}`);
  },

  updateOrderStatus(orderId: number, status: string): Promise<ApiResponse<PosOrder>> {
    return apiClient.patch<PosOrder>(`/pos/orders/${orderId}/status`, { status });
  },
};
