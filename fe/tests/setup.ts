import { afterEach } from "vitest";
afterEach(() => {
  sessionStorage.clear();
});
globalThis.ResizeObserver = class {
  observe() {}
  unobserve() {}
  disconnect() {}
};
