Branch creation and push commands

Run these locally to create per-person branches and push them to origin. Replace `origin` if your remote name differs.

Example commands (Windows PowerShell):

```
cd D:\web_project_full
git checkout main
git pull origin main

# Nati
git checkout -b feature/nati-pages
# stage only assigned files, then commit
git add index.html src/js/pages
git commit -m "Nati: add/update page views"
git push -u origin feature/nati-pages

# rebira
git checkout -b feature/rebira-api-helpers
git add frontend/src/js/api backend/bootstrap.php backend/src/Config/Database.php backend/database/schema.pgsql backend/scripts
git commit -m "rebira: API helpers and DB config"
git push -u origin feature/rebira-api-helpers

# iftu
git checkout -b feature/iftu-router-authguard
git add frontend/src/js/core backend/src/Middleware/AuthMiddleware.php backend/src/Controllers/UserController.php
git commit -m "iftu: router, auth-guard and user endpoints"
git push -u origin feature/iftu-router-authguard

# siham
git checkout -b feature/siham-requests-components
git add frontend/src/js/components frontend/src/assets backend/src/Controllers/RequestController.php backend/api/requests
git commit -m "siham: UI components and request endpoints"
git push -u origin feature/siham-requests-components

# petros
git checkout -b feature/petros-styles-inventory
git add frontend/src/css backend/src/Controllers/InventoryController.php backend/api/inventory backend/src/Services/AnalyticsService.php
git commit -m "petros: styles and inventory/analytics"
git push -u origin feature/petros-styles-inventory

# yididiya
git checkout -b feature/yididiya-tasks-qa
git add frontend/tests frontend/src/js small UI fixes backend/src/Controllers/TaskController.php backend/api/tasks uploads logs
git commit -m "yididiya: tasks, QA and uploads/logs"
git push -u origin feature/yididiya-tasks-qa
```

Notes:

- Use `git add -p` or explicit paths to avoid adding unrelated files.
- Create PRs from these branches and include the ASSIGNMENT.md mapping in the PR description.
