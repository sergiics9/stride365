export interface MediaItem {
  id: number;
  model_type: string;
  model_id: number;
  uuid: string;
  collection_name: string;
  name: string;
  file_name: string;
  mime_type: string | null;
  disk: string;
  conversions_disk: string | null;
  size: number;
  manipulations: Record<string, unknown>;
  custom_properties: Record<string, unknown>;
  generated_conversions: Record<string, unknown>;
  responsive_images: Record<string, unknown>;
  order_column: number | null;
  original_url?: string;
  preview_url?: string;
  created_at?: string;
  updated_at?: string;
}
