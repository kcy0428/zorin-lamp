# randomData — LAMP 센서 실시간 모니터링 시스템

Python 기반 센서 시뮬레이터가 1초마다 난수 데이터를 생성하여 MySQL에 저장하고,
Node-RED Dashboard와 Grafana가 실시간으로 시각화하는 통합 모니터링 시스템입니다.

---

## 디렉터리 구조

```
randomData/
├── injector.py              # 핵심: 센서 난수 생성 + MySQL 삽입
├── pyproject.toml           # Python 프로젝트 설정 (uv 관리)
├── uv.lock                  # 의존성 고정 파일
├── setup_db.sql             # MySQL DB/테이블 초기 설정 SQL
├── nodered_flow.json        # Node-RED 플로우 정의 (게이지 + 차트)
├── grafana/
│   ├── provisioning/
│   │   ├── datasources/
│   │   │   └── mysql.yml   # Grafana MySQL 데이터소스 자동 설정
│   │   └── dashboards/
│   │       └── dashboard.yml  # 대시보드 프로바이더 경로 설정
│   └── dashboards/
│       └── sensor_dashboard.json  # Grafana 대시보드 패널 정의
├── setup.sh                 # 전체 설치 자동화 스크립트 (1회 실행)
├── start.sh                 # 서비스 시작 스크립트
├── TROUBLESHOOTING.md       # 작업 중 발생한 문제 및 해결 기록
└── README.md                # 이 파일
```

---

## 각 파일/폴더 설명

### `injector.py`
시스템의 핵심 스크립트.

- **역할**: 가상 센서 데이터를 1초마다 생성하여 MySQL `sensor_readings` 테이블에 삽입
- **알고리즘**: 사인파 + 가우시안 노이즈 조합으로 실제 센서처럼 자연스러운 변화 시뮬레이션
- **생성 데이터**: 온도(°C), 습도(%), 기압(hPa), 조도(lux)
- **시간**: UTC 기준 명시 삽입 (`datetime.utcnow()`) → timezone 불일치 방지
- **실행**: `uv run injector.py`

### `pyproject.toml` / `uv.lock`
- **역할**: Python 가상환경 및 의존성 관리 (`uv` 사용)
- **의존성**: `pymysql`, `cryptography` (MySQL 8.0 인증 필요)
- **사용**: `uv sync` 으로 가상환경 설치, `uv run` 으로 실행

### `setup_db.sql`
- **역할**: MySQL `sensor_db` 데이터베이스와 `sensor_readings` 테이블 초기 생성
- **실행**: `sudo mysql < setup_db.sql` (1회만)
- **생성 내용**: DB 생성, `sensor_user` 계정 생성, 테이블 스키마 정의

### `nodered_flow.json`
- **역할**: Node-RED에 임포트할 플로우 전체 정의
- **설치 위치**: `~/.node-red/flows.json`으로 복사
- **플로우 구성**:
  - `inject` 노드: 3초 타이머
  - `function` 노드: SQL 쿼리 설정
  - `mysql` 노드: `sensor_db` SELECT 쿼리 실행
  - `ui_gauge` × 4: 온도/습도/기압/조도 실시간 게이지
  - `ui_chart`: 시계열 라인 차트

### `grafana/`

#### `grafana/provisioning/datasources/mysql.yml`
- **역할**: Grafana 시작 시 MySQL 데이터소스를 자동으로 등록
- **핵심 설정**:
  - `timezone: "+09:00"` (KST 세션, `UTC` 문자열은 MySQL이 미지원)
  - `timeInterval: "1s"` (1초 주기 데이터에 맞춘 최소 간격)
- **설치**: `/etc/grafana/provisioning/datasources/`에 복사

#### `grafana/provisioning/dashboards/dashboard.yml`
- **역할**: Grafana에게 대시보드 JSON 파일 위치를 알려주는 프로바이더 설정
- **경로**: `/var/lib/grafana/dashboards` (grafana 유저 접근 가능한 위치)
- **설치**: `/etc/grafana/provisioning/dashboards/`에 복사

#### `grafana/dashboards/sensor_dashboard.json`
- **역할**: Grafana 대시보드 패널 전체 정의
- **패널 구성**:
  - Stat 패널 × 4: 온도/습도/기압/조도 현재값 (색상 임계값 포함)
  - Time-series 패널: 4개 센서 복합 시계열 (min/max/mean/last 범례)
  - Table 패널: Raw 데이터 최근 100행
- **SQL**: `FROM_UNIXTIME(${__from:date:seconds})` 방식으로 timezone 독립적 시간 필터
- **설치**: `/var/lib/grafana/dashboards/`에 복사

### `setup.sh`
- **역할**: 전체 시스템 1회 설치 자동화
- **수행 작업**:
  1. `sudo mysql < setup_db.sql` — DB 생성
  2. `uv sync` — Python 가상환경 설치
  3. `npm install -g node-red` + 플러그인 설치
  4. Grafana apt 설치 + 프로비저닝 파일 복사 + 서비스 시작
- **실행**: `bash setup.sh`

### `start.sh`
- **역할**: 매번 시스템 시작 시 사용
- **수행 작업**:
  1. MySQL 연결 확인
  2. Grafana 서비스 시작 (systemd)
  3. Node-RED 백그라운드 시작
  4. `uv run injector.py` 포그라운드 실행 (Ctrl+C로 종료)
- **실행**: `bash start.sh`

### `TROUBLESHOOTING.md`
- **역할**: 개발 중 발생한 문제점과 해결 방법 기록
- **주요 내용**: cryptography 누락, Node-RED 자격증명 초기화, timezone 불일치, 이중 프로세스 문제 등

---

## 빠른 시작

```bash
# 1. 설치 (최초 1회)
bash setup.sh

# 2. 실행
bash start.sh
```

또는 개별 실행:
```bash
# 터미널 1 — Node-RED
node-red

# 터미널 2 — 인젝터
uv run injector.py
```

---

## 접속 주소

| 서비스 | URL | 계정 |
|--------|-----|------|
| Node-RED Dashboard | http://localhost:1880/ui/ | 없음 |
| Node-RED Editor | http://localhost:1880 | 없음 |
| Grafana | http://localhost:3000 | admin / admin |

---

## 데이터베이스

- **DB**: `sensor_db`
- **테이블**: `sensor_readings`
- **계정**: `sensor_user` / `Sensor@Pass1`
- **시간 기준**: UTC 저장 (`datetime.utcnow()`)

```sql
CREATE TABLE sensor_readings (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    recorded_at DATETIME(3) NOT NULL,   -- UTC 기준
    temperature DECIMAL(5,2),           -- °C
    humidity    DECIMAL(5,2),           -- %
    pressure    DECIMAL(7,2),           -- hPa
    light_level INT,                    -- lux
    INDEX idx_recorded_at (recorded_at)
);
```

