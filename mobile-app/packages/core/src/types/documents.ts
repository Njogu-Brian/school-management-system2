export interface DocumentListRecord {
  id: number;
  title: string;
  description?: string | null;
  category?: string | null;
  document_type?: string | null;
  file_name?: string | null;
  file_type?: string | null;
  file_size?: number | null;
  file_url?: string | null;
  download_path?: string | null;
  expiry_date?: string | null;
  is_expired?: boolean;
  is_expiring_soon?: boolean;
  created_at?: string;
  updated_at?: string;
}
