# Use a slim Python base
FROM python:3.12-slim

# Create working directory
WORKDIR /app

# Install system deps (optional but nice to have for pip wheels)
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
  && rm -rf /var/lib/apt/lists/*

# Install Python requirements
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy app
COPY app/ ./app

# Expose port
EXPOSE 8000

# Run with Waitress
CMD ["python", "-m", "app.__main__"]
