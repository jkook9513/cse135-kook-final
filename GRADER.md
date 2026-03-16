# CSE135 HW Analytics Project — Grader Guide

## Live Website

https://reporting.cse135-kook.site

---

## Login Credentials

Super Admin
username: admin
password: superadmin-PW

Analyst
username: analyst
password: analyst-PW

Viewer
username: viewer
password: viewer-PW

---

## Main Pages

Homepage
https://reporting.cse135-kook.site

Login
https://reporting.cse135-kook.site/login.php

Dashboard
https://reporting.cse135-kook.site/reports.php

Saved Reports
https://reporting.cse135-kook.site/saved_reports.php

---

## Features to Test

### Authentication

Verify role-based login works.

Viewer
- can access saved reports
- cannot access analytics dashboard

Analyst / Super Admin
- can access dashboard
- can export reports

---

### Analytics Dashboard

The dashboard contains:

- Page Activity Report
- Event Type Distribution
- Site Origin Analysis
- Recent Event Table
- Summary metrics

Charts are generated using Chart.js.

---

### Export System

Click **Export PDF** on the dashboard.

This generates a styled PDF containing:

- charts
- tables
- analyst comments
- summary statistics

The generated report is saved and appears in:

Saved Reports page.

---

### System Status Panel

The homepage includes a status panel showing:

- total events
- last recorded event
- database connectivity

---

## Areas to improve


I would like to improve the styling of both the dashboard and the analytics report. There is a bug that I was unable to fix in time, where the analytics data doesn't seem to be collecting from the test site anymore. Additionally, I feel that I could have structured things much better if I were to restart. 
