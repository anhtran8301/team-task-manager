import { http } from "@/api/http";
import type { ApiResult } from "@/typings/api";

export class BaseService {
  /** Read envelope data and let HTTP failures propagate to the caller. */
  protected async get<T>(url: string, params?: object): Promise<T> {
    return (await http.get<ApiResult<T>>(url, { params })).data.data;
  }

  /** Create a resource or invoke a command using the shared API contract. */
  protected async post<T>(url: string, data?: object): Promise<T> {
    return (await http.post<ApiResult<T>>(url, data)).data.data;
  }

  /** Replace editable fields using a validated, typed payload. */
  protected async put<T>(url: string, data: object): Promise<T> {
    return (await http.put<ApiResult<T>>(url, data)).data.data;
  }
}
