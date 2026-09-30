import { describe, expect, it } from "vitest";
import { taskQuery, taskSort } from "@/helpers/taskQuery";

describe("task list query", () => {
  it("maps ordered table criteria and rejects unsupported keys/orders", () => {
    expect(
      taskSort([
        { key: "assignee.name", order: "asc" },
        { key: "due_date", order: "desc" },
        { key: "status", order: "asc" },
        { key: "title", order: false },
        { key: "toString", order: "asc" },
      ]),
    ).toEqual([
      { field: "assignee", direction: "asc" },
      { field: "due_date", direction: "desc" },
    ]);
  });
  it("serializes lists and sort priority with brackets without coercing search text", () => {
    const params = taskQuery({
      page: 2,
      per_page: 20,
      search: "0 & orchid",
      status: ["todo", "done"],
      assigned_to: [2, 3],
      sort: [
        { field: "assignee", direction: "asc" },
        { field: "title", direction: "desc" },
      ],
    });
    expect(params.getAll("status[]")).toEqual(["todo", "done"]);
    expect(params.getAll("assigned_to[]")).toEqual(["2", "3"]);
    expect(params.get("sort[0][field]")).toBe("assignee");
    expect(params.get("sort[1][direction]")).toBe("desc");
    expect(params.get("search")).toBe("0 & orchid");
    expect([
      ...taskQuery({
        page: 1,
        per_page: 20,
        status: [],
        assigned_to: [],
        sort: [],
      }).keys(),
    ]).toEqual(["page", "per_page"]);
  });
});
