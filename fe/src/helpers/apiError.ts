import axios from "axios";
import type { ApiResult, FieldErrors } from "@/typings/api";

/** Internal codes remain available to callers; display the server's English messages. */
export function apiMessage(
  message: Record<string, string> | undefined,
): string {
  return message ? Object.values(message).join(" ") : "";
}

export function apiError(error: unknown): {
  message: string;
  fields: FieldErrors;
} {
  if (axios.isAxiosError<ApiResult<null>>(error)) {
    return {
      message:
        apiMessage(error.response?.data?.message) ||
        (error.response
          ? "Request failed. Please try again."
          : "Unable to connect. Please check your connection and retry."),
      fields: error.response?.data?.errors ?? {},
    };
  }

  return {
    message: "An unexpected error occurred. Please try again.",
    fields: {},
  };
}
