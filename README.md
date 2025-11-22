# Quan Ly Chi Tieu - Docker setup

## Requirements
- Docker + Docker Compose v2
- Copy `.env.example` to `.env` if you want to override defaults

## Run the stack
1. `docker compose up --build`
2. App is served at http://localhost:8080 (change with `APP_PORT` in `.env`)
3. MySQL is exposed on host port `MYSQL_PORT` (defaults to 3306)

## Database
- Schema/data from `qlct.sql` is loaded automatically on first start via `docker-entrypoint-initdb.d`
- PHP accepts envs: `DB_HOST`, `DB_PORT`, `DB_NAME`/`DB_DATABASE`, `DB_USER`/`DB_USERNAME`, `DB_PASS`/`DB_PASSWORD`
- Default credentials: `DB_USER=app`, `DB_PASS=app_password`, `DB_NAME=expenditure_management`, `MYSQL_ROOT_PASSWORD=root`
- Seed demo login: username `demo_user`, email `demo@example.com`, password `123456`
- To reset data: `docker compose down -v` then `docker compose up --build`
- Unicode bị lỗi do DB cũ? Hãy `docker compose down -v` để xóa volume rồi `docker compose up --build` cho MySQL nạp lại `qlct.sql` UTF-8 + emoji.

## Useful commands
- Shell into PHP container: `docker compose exec app bash`
- MySQL CLI: `docker compose exec db mysql -uapp -papp_password expenditure_management`
