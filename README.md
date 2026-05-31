# Spese

App per il tracciamento delle spese familiari, basata su Symfony 4.4 e Google Sheets API.

## Requisiti

- PHP 7.3+
- Composer
- Credenziali Google Sheets API (`api-google-sheet-secret.json`)

## Deploy con Docker su Raspberry Pi

### Prerequisiti

1. **Docker** installato sul Raspberry Pi:
   ```bash
   curl -fsSL https://get.docker.com | sh
   sudo usermod -aG docker $USER
   # Riavvia la sessione o fai logout/login
   ```

2. **Docker Compose** (incluso nelle versioni recenti di Docker):
   ```bash
   docker compose version
   ```

### Setup

1. **Clona il repository** sul Raspberry Pi:
   ```bash
   git clone https://github.com/henrydekap/spese.git
   cd spese
   ```

2. **Copia il file delle credenziali Google** nella root del progetto:
   ```bash
   # Copia api-google-sheet-secret.json nella directory del progetto
   cp /percorso/al/tuo/api-google-sheet-secret.json ./
   ```

3. **Configura le variabili d'ambiente**:
   ```bash
   cp .env.docker .env
   nano .env
   ```
   
   Modifica almeno:
   - `APP_SECRET` — genera una chiave random: `php -r "echo bin2hex(random_bytes(16));"`
   - `SPREADSHEET_ID` — l'ID del tuo Google Spreadsheet

4. **Avvia l'applicazione**:
   ```bash
   docker compose up -d --build
   ```

5. **Accedi all'app** dal browser:
   ```
   http://<IP-RASPBERRY-PI>:8080
   ```

### Comandi utili

```bash
# Visualizza i log in tempo reale
docker compose logs -f

# Riavvia l'app
docker compose restart

# Ferma l'app
docker compose down

# Ricostruisci dopo un aggiornamento del codice
git pull
docker compose up -d --build

# Accedi al container per debug
docker compose exec app bash
```

### Aggiornamento

```bash
cd spese
git pull
docker compose up -d --build
```

L'app verrà ricostruita con il codice aggiornato e riavviata automaticamente.

## Sviluppo locale

```bash
composer install
# Configura .env.local con le tue credenziali
php bin/console server:run
```