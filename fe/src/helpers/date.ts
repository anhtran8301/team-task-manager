/** Preserve the API calendar date without parsing it as a timezone-dependent timestamp. */
export function displayDueDate(value: string | null): string {
  return value ?? "No due date";
}
