#!/bin/bash
# ============================================================
# setup.sh - 센서 모니터링 시스템 초기 설치 스크립트
# 실행: bash setup.sh
# ============================================================

set -e
PROJ_DIR="$(cd "$(dirname "$0")" && pwd)"
NR_DATA="$HOME/.node-red"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*"; }

echo "============================================================"
echo "  LAMP 센서 모니터링 시스템 설치"
echo "  프로젝트: $PROJ_DIR"
echo "============================================================"
echo ""

# ── 1. MySQL DB 생성 ─────────────────────────────────────────
info "1/5 MySQL 센서 DB 설정 중..."
if sudo mysql < "$PROJ_DIR/setup_db.sql" 2>/dev/null; then
    info "    sensor_db 생성 완료 (sensor_user / Sensor@Pass1)"
else
    warn "    sudo mysql 실패. 아래 명령을 수동 실행하세요:"
    warn "      sudo mysql < $PROJ_DIR/setup_db.sql"
fi

# ── 2. uv 가상환경 & 의존성 설치 ────────────────────────────
info "2/5 Python 가상환경 설정 중 (uv)..."
if ! command -v uv &>/dev/null; then
    error "uv 미설치. 설치 후 재실행: curl -LsSf https://astral.sh/uv/install.sh | sh"
    exit 1
fi
cd "$PROJ_DIR"
uv sync
info "    가상환경 및 pymysql 설치 완료 (.venv)"

# ── 3. Node-RED 설치 ─────────────────────────────────────────
info "3/5 Node-RED 설치 중..."
if ! command -v node-red &>/dev/null; then
    npm install -g node-red --silent
    info "    node-red 설치 완료"
else
    info "    node-red 이미 설치됨: $(node-red --version 2>/dev/null)"
fi

# Node-RED 데이터 디렉토리 초기화
mkdir -p "$NR_DATA"
# 플로우 파일 복사
cp "$PROJ_DIR/nodered_flow.json" "$NR_DATA/flows.json"
cp "$PROJ_DIR/nodered_cred.json" "$NR_DATA/flows_cred.json"
chmod 600 "$NR_DATA/flows_cred.json"

# Node-RED 플러그인 설치
info "    Node-RED 플러그인 설치 중 (dashboard, mysql)..."
cd "$NR_DATA"
if [ ! -f package.json ]; then
    echo '{"name":"node-red-project","version":"1.0.0","description":""}' > package.json
fi
npm install --silent node-red-dashboard node-red-node-mysql
cd "$PROJ_DIR"
info "    Node-RED 플러그인 설치 완료"

# ── 4. Grafana 설치 ──────────────────────────────────────────
info "4/5 Grafana 설치 중..."
if ! command -v grafana-server &>/dev/null && ! systemctl is-active grafana-server &>/dev/null 2>&1; then
    if ! apt-cache show grafana &>/dev/null 2>&1; then
        info "    Grafana apt 저장소 추가 중..."
        sudo apt-get install -y -q apt-transport-https software-properties-common wget 2>/dev/null
        sudo mkdir -p /etc/apt/keyrings
        wget -q -O - https://apt.grafana.com/gpg.key | \
            sudo gpg --dearmor -o /etc/apt/keyrings/grafana.gpg
        echo "deb [signed-by=/etc/apt/keyrings/grafana.gpg] https://apt.grafana.com stable main" | \
            sudo tee /etc/apt/sources.list.d/grafana.list > /dev/null
        sudo apt-get update -q
    fi
    sudo apt-get install -y -q grafana
    info "    Grafana 설치 완료"
else
    info "    Grafana 이미 설치됨"
fi

# Grafana 프로비저닝 심볼릭 링크
GRAFANA_PROV="/etc/grafana/provisioning"
if [ -d "$GRAFANA_PROV" ]; then
    info "    Grafana 프로비저닝 설정 중..."
    sudo cp "$PROJ_DIR/grafana/provisioning/datasources/mysql.yml" \
            "$GRAFANA_PROV/datasources/sensor_mysql.yml"
    sudo cp "$PROJ_DIR/grafana/provisioning/dashboards/dashboard.yml" \
            "$GRAFANA_PROV/dashboards/sensor_dashboard.yml"
    info "    프로비저닝 파일 복사 완료"
fi

sudo systemctl enable grafana-server --now 2>/dev/null || true
info "    Grafana 서비스 시작됨"

# ── 5. 완료 ──────────────────────────────────────────────────
echo ""
echo "============================================================"
info "5/5 설치 완료!"
echo ""
echo "  서비스 시작:"
echo "    bash $PROJ_DIR/start.sh"
echo ""
echo "  접속 URL:"
echo "    Node-RED Dashboard : http://localhost:1880/ui"
echo "    Node-RED Editor    : http://localhost:1880"
echo "    Grafana            : http://localhost:3000  (admin / admin)"
echo "============================================================"
