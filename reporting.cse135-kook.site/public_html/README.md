# HW4 - Derisk Checkpoint

## URL
https://reporting.cse135-kook.site/login.php

## Credentials
- Username: admin
- Password: admin-PW

## 1. MVC-style app with authentication and navigation
This reporting app was built in PHP on the reporting subdomain. It includes a login page, a protected reports page, and a logout page. Session-based authentication is used to prevent forceful browsing. Users who try to access `reports.php` without logging in are redirected to `login.php`.

## 2. Datastore connected to a data table/grid
The reports page displays raw analytics event data from the MySQL `collector_events` table. The frontend fetches this data from `api/table.php` and renders it into an HTML table.

## 3. Datastore connected to a chart
The reports page includes a Chart.js bar chart showing the top pages by event count. The frontend fetches aggregated chart data from `api/chart.php`, which queries the `collector_events` table and groups results by `page_url`.
