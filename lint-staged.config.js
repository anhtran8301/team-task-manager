const quote = (file) => `'${file.replaceAll("'", "'\\''")}'`;
export default {
  "fe/**/*.{ts,vue,js}": (files) => [
    `node fe/node_modules/eslint/bin/eslint.js --config fe/eslint.config.js --fix ${files.map(quote).join(" ")}`,
    `prettier --write ${files.map(quote).join(" ")}`,
  ],
  "be/**/*.php": (files) =>
    `php be/vendor/bin/pint --config be/pint.json ${files.map(quote).join(" ")}`,
  "*.{json,md,yml,yaml,css,html}": "prettier --write",
  "*.js": (files) => {
    const rootFiles = files.filter((file) => !file.includes("/fe/"));
    return rootFiles.length
      ? `prettier --write ${rootFiles.map(quote).join(" ")}`
      : [];
  },
};
