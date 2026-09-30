import type { Role } from "@/common/enums";

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResult {
  expires_at: string;
  user: User;
}
