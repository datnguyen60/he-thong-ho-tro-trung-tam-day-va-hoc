# Setup Guide

## 1. Clone Repository
```sh
git clone <repo-url>
cd <repo-folder>
```

## 2. Set Folder Permissions
```sh
sudo mkdir -p mariadb_data moodle moodle_data
sudo chown -R 1001:1001 mariadb_data moodle moodle_data
sudo chmod -R 777 mariadb_data moodle moodle_data
```

## 3. Start Services with Docker Compose
```sh
docker-compose up -d
```

## 4. View Logs
```sh
docker-compose logs -f
```

## 5. Import Database (If Not Available)
Wait until MariaDB is fully initialized, then run:
```sh
docker exec -i mariadb /opt/bitnami/mariadb/bin/mariadb -u moodle -p'moodlepassword' moodle < ./moodle_backup.sql
```

## 6. Restart Docker Compose
```sh
docker-compose down
docker-compose up -d
```

## 7. Verify Setup
Check logs to confirm services are running:
```sh
docker-compose logs -f
```
If everything is set up correctly, you should be able to access the application at:
```
http://localhost:8080
```
Once accessible, you can proceed with developing plugins.

