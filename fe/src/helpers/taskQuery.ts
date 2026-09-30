import type {
  TableSort,
  TaskFilters,
  TaskSort,
  TaskSortField,
} from "@/typings/task";

const sortFields: Readonly<Record<string, TaskSortField>> = {
  title: "title",
  "assignee.name": "assignee",
  due_date: "due_date",
};

/** Translate table keys without leaking presentation paths into the public API. */
export function taskSort(items: TableSort[]): TaskSort[] {
  return items.flatMap(({ key, order }) => {
    const field = Object.hasOwn(sortFields, key) ? sortFields[key] : undefined;
    return field && (order === "asc" || order === "desc")
      ? [{ field, direction: order }]
      : [];
  });
}

/** Explicit bracket serialization keeps list shape and sort priority stable. */
export function taskQuery(filters: TaskFilters): URLSearchParams {
  const params = new URLSearchParams({
    page: String(filters.page),
    per_page: String(filters.per_page),
  });
  if (filters.search) params.set("search", filters.search);
  filters.status?.forEach((value) => params.append("status[]", value));
  filters.assigned_to?.forEach((value) =>
    params.append("assigned_to[]", String(value)),
  );
  filters.sort?.forEach(({ field, direction }, index) => {
    params.set(`sort[${index}][field]`, field);
    params.set(`sort[${index}][direction]`, direction);
  });
  return params;
}
