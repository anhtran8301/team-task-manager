import { expect, test, type Page } from "@playwright/test";
import type { APIRequestContext } from "@playwright/test";
const cleanup: number[] = [];
async function csrfHeaders(request: APIRequestContext) {
  await request.get("/api/csrf-cookie");
  const state = await request.storageState();
  const token = state.cookies.find(
    (cookie) => cookie.name === "XSRF-TOKEN",
  )?.value;
  if (!token) throw new Error("Missing CSRF cookie");
  return { "X-XSRF-TOKEN": decodeURIComponent(token) };
}
test.afterEach(async ({ request }) => {
  if (!cleanup.length) return;
  await request.post("/api/login", {
    headers: await csrfHeaders(request),
    data: { email: "admin@example.com", password: "Password123!" },
  });
  for (const id of cleanup.splice(0)) {
    const response = await request.delete(`/api/tasks/${id}`, {
      headers: await csrfHeaders(request),
    });
    expect([200, 404]).toContain(response.status());
  }
  await request.post("/api/logout", { headers: await csrfHeaders(request) });
});
async function login(page: Page, email: string) {
  await page.goto("/login");
  await page.getByLabel("Email address").fill(email);
  await page.getByLabel("Password", { exact: true }).fill("Password123!");
  const loginResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/login") &&
      response.request().method() === "POST",
  );
  await page.getByRole("button", { name: "Sign in", exact: true }).click();
  const response = await loginResponse;
  expect(response.status()).toBe(200);
  const identity = (await response.json()).data.user;
  expect(identity.role).toBe(email === "admin@example.com" ? "admin" : "user");
  expect(identity).not.toHaveProperty("roles");
  expect(identity).not.toHaveProperty("permissions");
  await expect(page).toHaveURL(/\/tasks$/);
}
async function search(page: Page, title: string) {
  await page.getByLabel("Search by title", { exact: true }).fill(title);
  await expect(page.getByRole("row").filter({ hasText: title })).toHaveCount(1);
}
test("admin assigns, two users are isolated, owner edits and deletes, session restores", async ({
  page,
  browser,
}) => {
  const title = `taskprobe${Date.now()}`;
  await login(page, "admin@example.com");
  await page.getByRole("button", { name: "New task" }).click();
  const dialog = page.getByRole("dialog");
  await dialog.getByLabel("Title", { exact: true }).fill(title);
  await dialog
    .getByLabel("Description (optional)")
    .fill("Created by the admin and assigned to Alex.");
  await dialog.getByLabel("Assignee", { exact: true }).press("ArrowDown");
  await page.getByRole("option", { name: "Alex Morgan" }).click();
  const createdResponse = page.waitForResponse(
    (response) =>
      response.url().endsWith("/api/tasks") &&
      response.request().method() === "POST",
  );
  await dialog
    .getByRole("button", { name: "Create task", exact: true })
    .click();
  const created = await createdResponse;
  expect(created.status()).toBe(201);
  cleanup.push((await created.json()).data.id);
  await expect(dialog).toBeHidden();
  await search(page, title);
  await page.getByRole("button", { name: "Log out" }).click();
  await expect(page).toHaveURL(/\/login$/);

  const otherContext = await browser.newContext();
  const other = await otherContext.newPage();
  await login(other, "sam@example.com");
  await other.getByLabel("Search by title", { exact: true }).fill(title);
  await expect(
    other.getByText("No tasks found.", { exact: false }),
  ).toBeVisible();
  await expect(
    other.getByRole("button", { name: `Edit ${title}`, exact: true }),
  ).toHaveCount(0);
  await otherContext.close();

  await login(page, "alex@example.com");
  await page.reload();
  await expect(page.getByRole("heading", { name: "My tasks" })).toBeVisible();
  await search(page, title);
  await page
    .getByRole("button", { name: `Edit ${title}`, exact: true })
    .click();
  await expect(dialog.getByLabel("Assignee", { exact: true })).toBeDisabled();
  await dialog.getByLabel("Status", { exact: true }).press("ArrowDown");
  await page.getByRole("option", { name: "Done", exact: true }).click();
  await dialog.getByRole("button", { name: "Save changes" }).click();
  await expect(dialog).toBeHidden();
  await expect(
    page
      .getByRole("row")
      .filter({ hasText: title })
      .getByText("Done", { exact: true }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: `Delete ${title}`, exact: true })
    .click();
  await dialog
    .getByRole("button", { name: "Delete task", exact: true })
    .click();
  await expect(dialog).toBeHidden();
  await expect(
    page.getByText("No tasks found.", { exact: false }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Log out" }).click();
  await expect(page).toHaveURL(/\/login$/);
  await page.goto("/tasks");
  await expect(page).toHaveURL(/\/login$/);
});

test("filters reset pagination, validation is visible, expired tokens redirect", async ({
  page,
}) => {
  await login(page, "alex@example.com");
  await page.getByRole("button", { name: "Next page", exact: true }).click();
  await expect(page.getByText(/^21-\d+ of \d+$/)).toBeVisible();
  await page
    .getByRole("combobox", { name: "Status", exact: true })
    .press("ArrowDown");
  await page.getByRole("option", { name: "Done", exact: true }).click();
  await page
    .getByRole("combobox", { name: "Status", exact: true })
    .press("Escape");
  await expect(page.getByText(/^1-\d+ of \d+$/)).toBeVisible();
  await page.getByRole("button", { name: "New task" }).click();
  await page
    .getByRole("dialog")
    .getByRole("button", { name: "Create task", exact: true })
    .click();
  await expect(page.getByText("This field is required.")).toBeVisible();
  await page
    .getByRole("dialog")
    .getByRole("button", { name: "Cancel" })
    .click();
  await page.context().clearCookies({ name: "team_tasks_access_token" });
  await page.getByRole("button", { name: "Refresh tasks" }).click();
  await expect(page).toHaveURL(/\/login$/);
});

test("cookie credentials remain HttpOnly and mutations require CSRF", async ({
  page,
}) => {
  await login(page, "alex@example.com");
  const cookie = (await page.context().cookies()).find(
    (item) => item.name === "team_tasks_access_token",
  );
  expect(cookie?.httpOnly).toBe(true);
  expect(cookie?.sameSite).toBe("Lax");
  expect(await page.evaluate(() => document.cookie)).not.toContain(
    "team_tasks_access_token",
  );
  expect(
    await page.evaluate(() => sessionStorage.length + localStorage.length),
  ).toBe(0);
  const response = await page.request.post("/api/tasks", {
    data: { title: "Must not persist" },
  });
  expect(response.status()).toBe(419);
  expect((await response.json()).success).toBe(false);
  await page.getByRole("button", { name: "Log out" }).click();
  await expect(page).toHaveURL(/\/login$/);
});

test("admin reassignment changes which user sees the task", async ({
  request,
}) => {
  async function signIn(email: string) {
    const response = await request.post("/api/login", {
      headers: await csrfHeaders(request),
      data: { email, password: "Password123!" },
    });
    expect(response.status()).toBe(200);
  }
  await signIn("admin@example.com");
  const users = (await (await request.get("/api/users")).json()).data;
  const alex = users.find(
    (user: { email: string }) => user.email === "alex@example.com",
  );
  const sam = users.find(
    (user: { email: string }) => user.email === "sam@example.com",
  );
  const title = `reassignmentprobe${Date.now()}`;
  const payload = { title, status: "todo", assigned_to: alex.id };
  const created = await request.post("/api/tasks", {
    headers: await csrfHeaders(request),
    data: payload,
  });
  expect(created.status()).toBe(201);
  const id = (await created.json()).data.id;
  cleanup.push(id);
  const updated = await request.put(`/api/tasks/${id}`, {
    headers: await csrfHeaders(request),
    data: { ...payload, assigned_to: sam.id },
  });
  expect(updated.status()).toBe(200);
  await request.post("/api/logout", { headers: await csrfHeaders(request) });
  await signIn("alex@example.com");
  expect(
    (await (await request.get(`/api/tasks?search=${title}`)).json()).data.total,
  ).toBe(0);
  await request.post("/api/logout", { headers: await csrfHeaders(request) });
  await signIn("sam@example.com");
  expect(
    (await (await request.get(`/api/tasks?search=${title}`)).json()).data.total,
  ).toBe(1);
  await request.post("/api/logout", { headers: await csrfHeaders(request) });
});

test("multiple filters and ordered header sorting operate on server pages", async ({
  page,
}) => {
  await login(page, "admin@example.com");
  await page.getByRole("button", { name: "Next page", exact: true }).click();
  await expect(page.getByText(/^21-\d+ of \d+$/)).toBeVisible();
  const status = page.getByRole("combobox", { name: "Status", exact: true });
  await status.press("ArrowDown");
  await page.getByRole("option", { name: "To do", exact: true }).click();
  await page.getByRole("option", { name: "Done", exact: true }).click();
  await status.press("Escape");
  await expect(page.getByText(/^1-\d+ of \d+$/)).toBeVisible();
  const assignee = page.getByRole("combobox", {
    name: "Assignee",
    exact: true,
  });
  await assignee.press("ArrowDown");
  await page.getByRole("option", { name: "Admin", exact: true }).click();
  const filtered = page.waitForResponse((response) => {
    const url = new URL(response.url());
    return (
      url.pathname === "/api/tasks" &&
      url.searchParams.getAll("assigned_to[]").length === 2
    );
  });
  await page.getByRole("option", { name: "Alex Morgan", exact: true }).click();
  await assignee.press("Escape");
  const rows = (await (await filtered).json()).data.data;
  expect(rows.length).toBeGreaterThan(0);
  expect(
    rows.every(
      (row: { status: string; assignee: { name: string } }) =>
        ["todo", "done"].includes(row.status) &&
        ["Admin", "Alex Morgan"].includes(row.assignee.name),
    ),
  ).toBe(true);

  const title = page.getByRole("columnheader", { name: /Title/ });
  const due = page.getByRole("columnheader", { name: /Due date/ });
  await title.click();
  const sorted = page.waitForResponse(
    (response) =>
      new URL(response.url()).searchParams.get("sort[1][field]") === "due_date",
  );
  await due.click();
  expect((await sorted).status()).toBe(200);
  await expect(title.locator(".v-data-table-header__sort-badge")).toHaveText(
    "1",
  );
  await expect(due.locator(".v-data-table-header__sort-badge")).toHaveText("2");
  await page.getByRole("button", { name: "Next page", exact: true }).click();
  await expect(page.getByText(/^21-\d+ of \d+$/)).toBeVisible();
  const requests: URL[] = [];
  page.on("request", (request) => {
    const url = new URL(request.url());
    if (url.pathname === "/api/tasks" && request.method() === "GET")
      requests.push(url);
  });
  const descending = page.waitForResponse(
    (response) =>
      new URL(response.url()).searchParams.get("sort[0][direction]") === "desc",
  );
  await title.click();
  await descending;
  await expect(page.getByText(/^1-\d+ of \d+$/)).toBeVisible();
  expect(requests).toHaveLength(1);
  expect(requests[0]?.searchParams.get("page")).toBe("1");
  const removed = page.waitForResponse(
    (response) =>
      new URL(response.url()).searchParams.get("sort[0][field]") === "due_date",
  );
  await title.click();
  await removed;
  await expect(title.locator(".v-data-table-header__sort-badge")).toHaveCount(
    0,
  );
  await expect(due.locator(".v-data-table-header__sort-badge")).toHaveText("1");
  const oneStatus = page.waitForResponse((response) => {
    const url = new URL(response.url());
    return (
      url.pathname === "/api/tasks" &&
      url.searchParams.getAll("status[]").length === 1
    );
  });
  await page
    .locator(".v-select")
    .filter({ has: status })
    .getByRole("button", { name: "Close", exact: true })
    .first()
    .click();
  expect(
    (await (await oneStatus).json()).data.data.every(
      (row: { status: string }) => row.status === "done",
    ),
  ).toBe(true);
  const noStatus = page.waitForResponse((response) => {
    const url = new URL(response.url());
    return url.pathname === "/api/tasks" && !url.searchParams.has("status[]");
  });
  await page.getByRole("button", { name: "Clear Status", exact: true }).click();
  await noStatus;
  const noAssignee = page.waitForResponse((response) => {
    const url = new URL(response.url());
    return (
      url.pathname === "/api/tasks" && !url.searchParams.has("assigned_to[]")
    );
  });
  await page
    .getByRole("button", { name: "Clear Assignee", exact: true })
    .click();
  expect((await noAssignee).status()).toBe(200);
  await expect(due.locator(".v-data-table-header__sort-badge")).toHaveText("1");
  await page.getByRole("button", { name: "Log out" }).click();
});
