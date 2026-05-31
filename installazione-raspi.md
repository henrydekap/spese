# 1. Installa Docker
curl -fsSL https://get.docker.com | sudo sh
# 2. Aggiungi il tuo utente al gruppo docker
sudo usermod -aG docker $USER
# 3. Riloggati per applicare il gruppo
logout

docker run --rm hello-world


# backup
scp -r pi@sbrambapi4:~/backup C:\backup-raspi

# ===========================================================
# RESTORE COMPLETO
# ===========================================================

scp -r  C:\backup-raspi pi@sbrambapi4:~/backup

# --- 1. HOME ASSISTANT ---
# Ripristina la config
sudo mkdir -p /home/homeassistant
sudo tar xzf ~/backup/homeassistant-config.tar.gz -C /

# Ripristina il docker-compose di HA
cp ~/backup/docker-compose-ha.yaml ~/docker-compose.yaml

# Avvia Home Assistant
cd ~ && docker compose up -d

# --- 2. SPESE ---
sudo mkdir -p /var/www
cd /var/www
sudo chown $USER:$USER /var/www
git clone https://github.com/henrydekap/spese.git
cd spese

# Ripristina segreti
cp ~/backup/api-google-sheet-secret.json .
cp ~/backup/spese-env .env

# Avvia Spese
docker compose up --build -d

# --- 4. VERIFICA ---
echo "==> Containers attivi:"
docker ps

