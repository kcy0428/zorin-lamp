# randomData 트러블슈팅 기록

> 2026-03-26 작업 중 발생한 문제점과 해결 방법 정리

---

## 1. injector.py 실행 오류 — `cryptography` 패키지 없음

### 증상
```
[ERROR] 'cryptography' package is required for sha256_password or caching_sha2_password auth methods
```

### 원인
MySQL 8.0의 기본 인증 방식(`caching_sha2_password`)이 RSA 암호화를 요구하는데, `pymysql`만으로는 부족.

### 해결
```bash
uv add cryptography
```

---

## 2. Node-RED MySQL 노드 — `not yet connected`

### 증상
Node-RED 에디터에서 `sensor_readings 조회` 노드가 **not yet connected** 상태로 표시되고 대시보드에 데이터가 표시되지 않음.

### 원인
Node-RED는 시작 시 `flows_cred.json`에서 자격증명을 읽어 암호화 저장함.
`settings.js`의 `credentialSecret`이 주석 처리되어 있으면 **재시작마다 새 키가 생성**되어 이전에 암호화한 자격증명을 복호화하지 못함.

### 해결
1. `~/.node-red/settings.js`에 고정 키 설정:
   ```js
   credentialSecret: "sensor-lamp-2026",
   ```
2. `~/.node-red/flows_cred.json`을 평문으로 재작성:
   ```json
   {
     "db-cfg-001": {
       "user": "sensor_user",
       "password": "Sensor@Pass1"
     }
   }
   ```
3. Node-RED 재시작 → 시작 시 평문 자격증명을 고정 키로 암호화하여 저장.

---

## 3. Node-RED 차트 — `Bad data inject` 경고

### 증상
Node-RED 로그에 반복적으로 출력:
```
[warn] [ui_chart:센서 시계열] Bad data inject
```

### 원인
`node-red-dashboard v3`의 `ui_chart`는 `{payload: [array of {x,y}]}` 형식을 지원하지 않음.

### 해결
차트 함수 노드를 최신값 단순 숫자 전송 방식으로 변경:
```javascript
// 수정 전 (오류)
payload: rows.map(r => ({ x: new Date(r.recorded_at).getTime(), y: parseFloat(r.temperature) }))

// 수정 후 (정상)
{payload: parseFloat(r.temperature), topic: '온도(°C)'}
```
차트가 값을 누적하여 시계열 그래프를 자동으로 그림.

---

## 4. Node-RED MySQL timezone 경고

### 증상
```
Ignoring invalid timezone passed to Connection: Asia/Seoul.
```

### 원인
`node-red-node-mysql`이 내부적으로 `mysql2`를 사용하는데, `mysql2`는 `Asia/Seoul` 형식을 미지원.

### 해결
`nodered_flow.json`의 `MySQLdatabase` 설정에서 timezone 수정:
```json
"tz": "+09:00"   // "Asia/Seoul" → "+09:00"
```

---

## 5. Grafana 시작 실패 — `permission denied`

### 증상
```
Error: stat /home/chan/Desktop/.../grafana/dashboards: permission denied
```

### 원인
Grafana는 `grafana` 유저로 실행되어 `/home/chan` 디렉터리에 접근 불가.

### 해결
대시보드 JSON 파일을 Grafana 데이터 디렉터리로 복사:
```bash
sudo cp grafana/dashboards/sensor_dashboard.json /var/lib/grafana/dashboards/
```
프로비저닝 `path`를 `/var/lib/grafana/dashboards`로 변경.

---

## 6. Grafana 시계열 패널 — `Data outside time range`

### 증상
Grafana 대시보드에서 **"Data outside time range – Zoom to data"** 메시지만 표시되고 그래프가 그려지지 않음.

### 원인 (복합적)

#### 6-1. Datasource timezone 미설정
Grafana의 `$__timeFilter()`는 UTC 기준 Unix timestamp → `FROM_UNIXTIME()`으로 변환.
MySQL이 KST(+09:00) 세션에서 실행되면 `FROM_UNIXTIME()`이 KST 시각을 반환하여 UTC 저장 데이터와 9시간 불일치 발생.

#### 6-2. MySQL timezone 이름 미지원 (`UTC`)
```
Error 1298 (HY000): Unknown or incorrect time zone: 'UTC'
```
MySQL timezone 테이블이 로드되지 않아 `UTC` 문자열 인식 불가.

#### 6-3. 이중 프로세스 (KST/UTC 혼재 삽입)
VSCode 터미널에서 시작된 구버전 injector.py(PID 197641)가 백그라운드에 남아 KST로 계속 삽입.
수정된 injector.py는 UTC로 삽입 → DB에 KST/UTC 혼재.

### 해결

| 단계 | 조치 |
|------|------|
| 1 | 구버전 injector 프로세스 강제 종료: `kill 197641` |
| 2 | `injector.py`에서 `datetime.datetime.utcnow()`로 UTC 명시 삽입 |
| 3 | Grafana datasource timezone을 `"UTC"` → `"+00:00"`으로 변경 (MySQL이 명칭 대신 오프셋만 인식) |
| 4 | 대시보드 SQL에서 `$__timeFilter()` → `FROM_UNIXTIME(${__from:date:seconds})`로 교체 |
| 5 | 기존 혼재 데이터 삭제 후 UTC 데이터로 재적재 |

### 최종 검증
```sql
-- MySQL +00:00 세션에서 Grafana와 동일한 조건으로 866건 정상 조회
SET time_zone='+00:00';
SELECT COUNT(*) FROM sensor_readings
WHERE recorded_at BETWEEN FROM_UNIXTIME(1774508734) AND FROM_UNIXTIME(1774509334);
-- 결과: 866
```

---

## 핵심 교훈

1. **MySQL DATETIME + timezone**: `DATETIME`은 timezone 정보를 저장하지 않으므로, 삽입/조회 모두 동일한 timezone 기준을 사용해야 함. → **항상 UTC로 저장**
2. **Node-RED credentialSecret**: 재시작마다 키가 바뀌면 자격증명이 매번 초기화됨. → **settings.js에 고정 키 필수**
3. **백그라운드 프로세스 관리**: `kill %1`은 현재 쉘의 job만 종료. 다른 터미널에서 시작한 프로세스는 `pgrep`/`lsof`로 별도 확인 필요
4. **MySQL timezone 이름**: `UTC`, `Asia/Seoul` 같은 명칭은 timezone 테이블 로드 필요. → `+00:00`, `+09:00` 오프셋 형식 사용
