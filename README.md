# CSE135 Web Analytics Reporting Platform

## Overview

This project implements a web analytics collection and reporting system for CSE135.

The system collects client-side events, stores them in a database, and provides an analytics dashboard with visualizations and exportable reports.

The platform includes:

- Event collection system
- Database storage
- Analytics dashboard
- Chart visualizations
- PDF report exports
- Role-based authentication
- Saved report management

---

## Live Sites

https://reporting.cse135-kook.site

https://cse135-kook.site/
https://collector.cse135-kook.site/
https://test.cse135-kook.site/catalog.html

## Repo
https://github.com/jkook9513/cse135-kook.site
https://github.com/jkook9513/cse135-kook-final
---

## Features

### Event Collection
Client-side events are sent to a collector endpoint and stored in the database.

Tracked data includes:

- page URL
- event type
- session ID
- timestamp
- site origin

---

### Analytics Dashboard

The dashboard provides:

- page activity reports
- event type distributions
- site origin analysis
- recent event logs
- interactive charts
- summary metrics

---

### Report Export

Reports can be exported as **styled PDF documents** containing:

- charts
- analytics tables
- analyst comments
- summary statistics

Reports are stored and accessible from the **Saved Reports** page.

---

### Authentication System

Users are authenticated through the login system.

Roles include:

- super_admin
- analyst
- viewer

Role permissions control access to dashboards and reports.

---

### System Status

The homepage includes a system status panel displaying:

- event count
- last event timestamp
- database connectivity

---

## Technologies Used

Frontend
- HTML
- CSS
- JavaScript
- Chart.js

Backend
- PHP
- MySQL
- PDO

Libraries
- Dompdf (PDF generation)

---

## Running the Project

The project is deployed on an Ubuntu server using Apache and PHP.

Requirements:

- PHP
- MySQL
- Composer
- Apache

---

## AI Use

I used AI for various purposes for this project. Since I was working alone, it was useful for tedious work such as frontend design and bug fixes. It was also very useful for documentation like the README files. Overall, it saved me a lot of time and energy, and it was very useful throughout the course of this project

## Future Features

Given more time, I would like to expand on the data I am currently collection. I would also like to work on the frontend design more and have the navigation flow be more smooth. I would also like to work on a real authentication system with sign-ups.

## Extra Credit Documentation

- Frontend design of pages (login, dashboard, report, etc..) matches
- Password information is hashed before storing in database to ensure better security
- Added system status portion on reporting site
- Styled analysis reports (with charts, tables, etc) available for pdf export
