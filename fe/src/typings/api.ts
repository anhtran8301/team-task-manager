export interface ApiResult<T> {
  success: boolean;
  code: number;
  message: Record<string, string>;
  data: T;
  errors?: Record<string, string[]>;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  per_page: number;
  last_page: number;
  total: number;
}

export type FieldErrors = Record<string, string[]>;
