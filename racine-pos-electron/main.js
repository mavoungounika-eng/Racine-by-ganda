// Legacy compatibility entrypoint.
// Some local scripts may still launch electron main.js.
// Delegate to the maintained entrypoint to avoid loading backend web login pages.
require("./electron/main.js");
