# VirtualPirate Guest Book API

A cyberpunk-themed guest book API with persistent storage, rate limiting, and CORS support. Built with PHP, nginx, and Alpine Linux in a Docker container.

## Features

- **Nginx + PHP-FPM** in a single Alpine Linux container
- **Guest book API** with persistent JSON storage
- **CORS headers** configured for cross-origin requests from Neocities
- **Rate limiting** to prevent spam (60 seconds between posts per IP)
- **Input validation** and sanitization
- **Health checks** for container monitoring
- **Atomic file writes** to prevent data corruption

## Quick Start

### Local Development

```bash
# Clone and build
git clone https://github.com/VirtualP1rate/guestbook-api.git
cd guestbook-api

# No additional files needed - API only

# Start the container
docker-compose up -d

# View logs
docker-compose logs -f

# Test the API
curl http://localhost:8080/guestbook.php
```

### Production Deployment

```bash
# Pull the image from GitHub Container Registry
docker pull ghcr.io/virtualp1rate/guestbook-api:latest

# Run with Docker
docker run -d \
  --name guestbook-api \
  -p 80:80 \
  -v $(pwd)/data:/var/www/html/data \
  ghcr.io/virtualp1rate/guestbook-api:latest

# Or use with your existing nginx proxy manager
# Point your domain to this container on port 80
```

## API Endpoints

### GET /guestbook.php
Returns all guest book messages in JSON format.

**Response:**
```json
{
  "success": true,
  "messages": [
    {
      "name": "Username",
      "message": "Message text",
      "timestamp": "2025-01-15T12:00:00+00:00",
      "id": "msg_1642248000_a1b2c3d4"
    }
  ]
}
```

### POST /guestbook.php
Adds a new guest book message.

**Request:**
```json
{
  "name": "Username",
  "message": "Your message here"
}
```

**Response:**
```json
{
  "success": true,
  "message": {
    "name": "Username",
    "message": "Your message here",
    "timestamp": "2025-01-15T12:00:00+00:00",
    "id": "msg_1642248000_a1b2c3d4"
  }
}
```

## File Structure

```
guestbook-api/
├── Dockerfile              # Container definition
├── docker-compose.yml     # Local development setup
├── nginx.conf             # Nginx configuration
├── supervisord.conf       # Process management
├── guestbook.php          # Guest book API
├── .github/workflows/     # GitHub Actions for CI/CD
└── data/                  # Persistent data directory
    └── guestbook.json     # Guest book messages
```

## Configuration

### Environment Variables

- `PHP_MEMORY_LIMIT` - PHP memory limit (default: 128M)
- `PHP_MAX_EXECUTION_TIME` - PHP execution time (default: 30s)

### Volumes

- `/var/www/html/data` - Persistent storage for guest book messages
- `/var/www/html` - Website files (for development mounting)

### Ports

- `80` - HTTP web server

## Security Features

- **Input sanitization** - All user input is sanitized and validated
- **Rate limiting** - 60 second cooldown between posts per IP
- **Message limits** - Name (20 chars), Message (200 chars)
- **XSS protection** - HTML tags stripped, special chars escaped
- **File permissions** - Proper ownership and permissions set
- **Hidden files** - Nginx denies access to dotfiles and data directory

## Monitoring

Health check endpoint: `GET /guestbook.php`

The container includes built-in health checks that verify the API is responding correctly.

## Troubleshooting

### Check container status
```bash
docker ps
docker logs guestbook-api
```

### Check API health
```bash
curl -I http://localhost:8080/guestbook.php
```

### View guest book data
```bash
docker exec guestbook-api cat /var/www/html/data/guestbook.json
```

### Reset guest book data
```bash
docker exec guestbook-api rm /var/www/html/data/guestbook.json
docker restart guestbook-api
```