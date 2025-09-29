# Pyramid + MongoDB Experiment Submissions

This project provides a minimal **Pyramid web application** with a form to store experiment results in a **MongoDB** database.  
It runs entirely via **Docker Compose**.

---

## Features

- Collects experiment data via a web form:
  - Experiment identifier  
  - NIU  
  - First name  
  - Last name  
  - Result (numeric)  
  - Uncertainty (numeric)  
  - Rectifications counter (auto-incremented when edited)  
  - Comments (optional)  

- Stores all submissions in MongoDB.
- Lists all submissions in a table.
- Allows editing existing submissions, incrementing the rectification counter.
- Uses **Pyramid + Jinja2 + Waitress** for the backend.

---

## Project structure

```
pyramid-mongo-app/
├── docker-compose.yml         # Docker Compose services (web + mongo)
├── .env                       # Environment configuration
└── web/
    ├── Dockerfile             # Web app container definition
    ├── requirements.txt       # Python dependencies
    └── app/
        ├── __init__.py        # Pyramid app factory
        ├── __main__.py        # Entry point for Waitress
        ├── models.py          # MongoDB data access
        ├── views.py           # Pyramid views
        └── templates/         # Jinja2 templates
```

---

## Prerequisites

- [Docker](https://www.docker.com/)  
- [Docker Compose](https://docs.docker.com/compose/)

---

## Quick start

1. Clone this repository:

   ```bash
   git clone https://github.com/pbauza/pyramid-pymongo.git
   cd pyramid-mongo-app
   ```

2. Start the services:

   ```bash
   docker compose up --build
   ```

3. Open your browser:

   - Web app: [http://localhost:8000](http://localhost:8000)  
   - MongoDB (internal only): `mongodb://mongo:27017`

---

## Environment variables

All configurable via `.env`:

| Variable           | Default                 | Description                       |
|--------------------|-------------------------|-----------------------------------|
| `MONGO_URI`        | `mongodb://mongo:27017` | MongoDB connection string         |
| `MONGO_DB`         | `experiments`           | Database name                     |
| `MONGO_COLLECTION` | `submissions`           | Collection name                   |
| `PYRAMID_RELOAD`   | `false`                 | Enable dev reload (not in Docker) |
| `PORT`             | `8000`                  | Port used by Waitress server      |

---

## Development tips

- To run Pyramid with **auto-reload** in development, set:

  ```dotenv
  PYRAMID_RELOAD=true
  ```

  And override the entry command in the `Dockerfile` with:

  ```dockerfile
  CMD ["pserve", "development.ini", "--reload"]
  ```

- The default stack uses Waitress without reload for production stability.

---

## Example workflow

1. Open `/` and submit a new record.  
2. Check `/submissions` to see the list.  
3. Click **Edit** to update a record → rectifications counter increases automatically.
