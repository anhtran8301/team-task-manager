export interface FormField {
  key: string;
  label: string;
  type: "text" | "password" | "textarea" | "select" | "date";
  required?: boolean;
  disabled?: boolean;
  maxlength?: number;
  items?: { title: string; value: string | number }[];
}

export type FormValues = Record<string, string | number | null>;
