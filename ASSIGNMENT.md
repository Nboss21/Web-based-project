Project file assignment for 6 contributors

## Overview

This document maps frontend and backend files/folders to each team member so they can work on separate branches and open PRs for review.

## Assignments

- Nati
  - Frontend: index.html, src/js/pages/\*\* (all page views), src/js/pages/login.js
  - Backend: api/auth/_ (login.php, register.php, reset-_.php), src/Controllers/AuthController.php, src/Utils/JWTHandler.php

- rebira
  - Frontend: src/js/api/\*\* (client.js, auth.js, other API helpers)
  - Backend: bootstrap.php, src/Config/Database.php, database/schema.pgsql, scripts/\* (import, seed, test scripts)

- iftu
  - Frontend: src/js/core/\*\* (router.js, auth-guard.js, app bootstrap)
  - Backend: src/Middleware/AuthMiddleware.php, api/me/\* endpoints, src/Controllers/UserController.php

- siham
  - Frontend: src/js/components/** (UI components), src/assets/**
  - Backend: src/Controllers/RequestController.php, api/requests/\*, src/Services/NotificationService.php

- petros
  - Frontend: src/css/\*\* (base.css, components.css, layout.css) and visual tweaks
  - Backend: src/Controllers/InventoryController.php, api/inventory/\*, src/Services/AnalyticsService.php

- yididiya
  - Frontend: integration & QA, test pages, accessibility fixes, small components
  - Backend: src/Controllers/TaskController.php, api/tasks/\*, uploads/ and logs maintenance scripts

## Guidelines

- Each person creates a branch named `feature/<name>-<short-desc>` (examples in BRANCHES.md).
- Commit only files in your assignment scope.
- Open a PR with the `Assigned Developer` field filled and list files changed.
- Keep PRs small and focused; include screenshots if UI changes.

If you need different splits or want me to create branch stubs, tell me and I will generate branch commands.
