const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    pageLoadTimeout: 180000,
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});
