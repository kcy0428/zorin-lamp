# LAMP 센서 실시간 모니터링 시스템

## 개요

Python 기반 센서 시뮬레이터(injector.py)가 1초마다 온도·습도·기압·조도 난수를 생성하여 MySQL(sensor_db)에 저장하고, Node-RED Dashboard와 Grafana가 해당 데이터를 실시간으로 시각화하는 통합 모니터링 시스템입니다.

---

## 시스템 구성 요소

| 컴포넌트 | 역할 | 주소 |
|---|---|---|
| `injector.py` | 센서 난수 생성 → MySQL 삽입 (1초 간격) | 터미널 실행 |
| MySQL `sensor_db` | 센서 데이터 영구 저장 | `localhost:3306` |
| Node-RED | 3초 폴링 → 게이지/차트 대시보드 | `http://localhost:1880/ui` |
| Grafana | MySQL 직접 조회 → 분석 대시보드 (5초 갱신) | `http://localhost:3000` |

---

## 전체 동작 Flowchart

```mermaid
flowchart TD
    subgraph GEN["🐍 injector.py (Python)"]
        A1[시작] --> A2[DB 연결<br/>sensor_user @ sensor_db]
        A2 --> A3{루프 - 1초마다}
        A3 --> A4["난수 생성\n온도: 22°C ± 사인파 + 가우시안 노이즈\n습도: 60% ± 복합 사인파\n기압: 1013hPa ± 느린 사인파\n조도: 600lux ± 주기파"]
        A4 --> A5["MySQL INSERT\nsensor_readings 테이블"]
        A5 --> A6[콘솔 출력]
        A6 --> A3
        A3 -->|"Ctrl+C"| A7[종료]
    end

    subgraph DB["🗄️ MySQL - sensor_db"]
        B1[("sensor_readings\n─────────────\nid BIGINT PK\nrecorded_at DATETIME\ntemperature DECIMAL\nhumidity DECIMAL\npressure DECIMAL\nlight_level INT")]
    end

    subgraph NR["🔴 Node-RED (port 1880)"]
        C1["⏱ Inject Node\n3초 타이머"] --> C2["📝 Function\nSQL 쿼리 설정"]
        C2 --> C3["🔌 MySQL Node\nSELECT last 60 rows"]
        C3 --> C4["📊 Function\n최신값 추출 → 4출력"]
        C3 --> C5["📈 Function\n시계열 포맷 → 3출력"]
        C4 --> C6["🌡️ Gauge: 온도"]
        C4 --> C7["💧 Gauge: 습도"]
        C4 --> C8["🌬️ Gauge: 기압"]
        C4 --> C9["☀️ Gauge: 조도"]
        C5 --> C10["📉 Chart: 시계열"]
    end

    subgraph GF["📊 Grafana (port 3000)"]
        D1["⏱ Auto Refresh\n5초"] --> D2["🔌 MySQL Datasource\nSTOMP 직접 조회"]
        D2 --> D3["📌 Stat Panel: 온도"]
        D2 --> D4["📌 Stat Panel: 습도"]
        D2 --> D5["📌 Stat Panel: 기압"]
        D2 --> D6["📌 Stat Panel: 조도"]
        D2 --> D7["📈 Time-Series Panel\n4개 센서 복합"]
        D2 --> D8["📋 Table Panel\nRaw 데이터 100행"]
    end

    A5 -->|"INSERT"| B1
    B1 -->|"SELECT LIMIT 60"| C3
    B1 -->|"$__timeFilter()"| D2
```

---

## 데이터 흐름 상세

```mermaid
sequenceDiagram
    participant I as injector.py
    participant M as MySQL<br/>sensor_db
    participant NR as Node-RED
    participant GF as Grafana

    loop 1초마다
        I->>I: 사인파+가우시안으로 난수 생성
        I->>M: INSERT sensor_readings
        Note over M: id, recorded_at, temperature,<br/>humidity, pressure, light_level
    end

    loop 3초마다
        NR->>M: SELECT ... ORDER BY id DESC LIMIT 60
        M-->>NR: 최근 60행 반환
        NR->>NR: 최신값 파싱 → 4개 msg 분기
        NR->>NR: Dashboard Gauge 업데이트
        NR->>NR: 시계열 배열 포맷 → Chart 업데이트
    end

    loop 5초마다 (자동 갱신)
        GF->>M: rawSql WHERE $__timeFilter(recorded_at)
        M-->>GF: 시간 범위 내 데이터 반환
        GF->>GF: Stat / TimeSeries / Table 패널 렌더링
    end
```

---

## 난수 생성 알고리즘

각 센서값은 **사인파 + 가우시안 노이즈** 조합으로 실제 센서처럼 자연스러운 변화를 시뮬레이션합니다.

| 센서 | 기준값 | 변화 방식 | 범위 |
|---|---|---|---|
| 온도 | 22°C | 60초 주기 사인파 ± 0.3σ 노이즈 | -10 ~ 50°C |
| 습도 | 60% | 온도 역상관 + 300초 2차 사인파 ± 1σ | 0 ~ 100% |
| 기압 | 1013.25hPa | 600초 느린 사인파 ± 0.2σ | 950 ~ 1060hPa |
| 조도 | 600lux | 120초 일출/일몰 사인파 ± 30σ | 0 ~ 2000lux |

---

## 설치 및 실행

```bash
# 1. 초기 설치 (1회만 실행)
bash setup.sh

# 2. 시스템 전체 시작
bash start.sh

# 3. 개별 실행
node-red &                  # Node-RED
sudo systemctl start grafana-server   # Grafana
python3 injector.py         # 인젝터
```

---

## 디렉토리 구조

```
randomData/
├── injector.py                          # 센서 데이터 생성 및 MySQL 삽입
├── requirements.txt                     # Python 의존성 (pymysql)
├── setup_db.sql                         # MySQL DB/테이블 스키마
├── nodered_flow.json                    # Node-RED 플로우 정의
├── nodered_cred.json                    # Node-RED DB 자격증명
├── grafana/
│   ├── provisioning/
│   │   ├── datasources/mysql.yml        # Grafana MySQL 데이터소스
│   │   └── dashboards/dashboard.yml    # 대시보드 프로바이더 설정
│   └── dashboards/
│       └── sensor_dashboard.json       # Grafana 대시보드 정의
├── setup.sh                             # 통합 설치 스크립트
├── start.sh                             # 서비스 시작 스크립트
└── project.md                           # 프로젝트 문서 (현재 파일)
```
