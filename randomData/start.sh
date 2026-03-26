#!/bin/bash
# ============================================================
# start.sh - 센서 모니터링 시스템 실행
# 실행: bash start.sh
# ============================================================

PROJ_DIR="$(cd "$(dirname "$0")" && pwd)"
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[INFO]${NC}  $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $*"; }

echo "============================================================"
echo "  LAMP 센서 모니터링 시스템 시작"
echo "============================================================"

# MySQL 체크
if ! mysql -u sensor_user -p'Sensor@Pass1' -e "SELECT 1;" sensor_db &>/dev/null; then
    echo -e "${RED}[ERROR]${NC} MySQL sensor_db 접속 실패"
    echo "         먼저 setup.sh를 실행하세요: bash setup.sh"
    exit 1
fi
info "MySQL sensor_db 연결 확인"

# Grafana 시작
if systemctl is-active --quiet grafana-server 2>/dev/null; then
    info "Grafana 이미 실행 중"
else
    sudo systemctl start grafana-server 2>/dev/null && info "Grafana 시작됨" || warn "Grafana 시작 실패 (수동 시작 필요)"
fi

# Node-RED 백그라운드 시작
if pgrep -f "node-red" > /dev/null; then
    info "Node-RED 이미 실행 중 (PID: $(pgrep -f node-red))"
else
    info "Node-RED 백그라운드 시작..."
    nohup node-red > "$PROJ_DIR/nodered.log" 2>&1 &
    NR_PID=$!
    echo $NR_PID > "$PROJ_DIR/nodered.pid"
    sleep 3
    info "Node-RED 시작됨 (PID: $NR_PID) | 로그: $PROJ_DIR/nodered.log"
fi

# 인젝터 시작
info "injector.py 시작 (Ctrl+C 로 종료)..."
echo ""
echo "  접속 URL:"
echo "    Node-RED Dashboard : http://localhost:1880/ui"
echo "    Node-RED Editor    : http://localhost:1880"
echo "    Grafana            : http://localhost:3000  (admin / admin)"
echo "============================================================"
echo ""

uv run "$PROJ_DIR/injector.py"

# 정리
info "인젝터 종료됨. Node-RED 중지 중..."
if [ -f "$PROJ_DIR/nodered.pid" ]; then
    kill "$(cat "$PROJ_DIR/nodered.pid")" 2>/dev/null && rm -f "$PROJ_DIR/nodered.pid"
fi
info "완료"
