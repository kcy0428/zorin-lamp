#!/usr/bin/env python3
"""
injector.py - 센서 난수 데이터 생성 및 MySQL 주입
1초마다 온도/습도/기압/조도 난수를 생성하여 sensor_db에 삽입합니다.
"""

import time
import random
import math
import signal
import sys
from datetime import datetime

try:
    import pymysql
except ImportError:
    print("[ERROR] pymysql 미설치: python3 -m pip install pymysql")
    sys.exit(1)

# ── DB 접속 설정 ────────────────────────────────────────────
DB_CONFIG = {
    "host":    "127.0.0.1",
    "port":    3306,
    "user":    "sensor_user",
    "password":"Sensor@Pass1",
    "database":"sensor_db",
    "charset": "utf8mb4",
}

INTERVAL = 1.0   # 주입 간격 (초)
running  = True  # Ctrl+C 종료 플래그


def get_connection():
    return pymysql.connect(**DB_CONFIG)


def generate_sensor_data(tick: int) -> dict:
    """
    실제 센서처럼 사인파 + 노이즈를 섞어 자연스러운 난수 생성
    tick : 경과 시간(초)
    """
    # 온도: 22°C 기준 ±5°C, 느린 사인파 + 작은 노이즈
    temperature = (
        22.0
        + 5.0  * math.sin(tick / 60)
        + random.gauss(0, 0.3)
    )

    # 습도: 60% 기준 ±20%, 온도와 역상관
    humidity = (
        60.0
        - 3.0  * math.sin(tick / 60)
        + 15.0 * math.sin(tick / 300 + 1.2)
        + random.gauss(0, 1.0)
    )

    # 기압: 1013 hPa 기준 ±10 hPa, 매우 느린 변화
    pressure = (
        1013.25
        + 8.0  * math.sin(tick / 600)
        + random.gauss(0, 0.2)
    )

    # 조도: 주간(낮 주기 1분) 0~1200 lux
    light_level = max(0, int(
        600
        + 550  * math.sin(tick / 120 - math.pi / 2)
        + random.gauss(0, 30)
    ))

    return {
        "temperature": round(max(-10, min(50,  temperature)), 2),
        "humidity":    round(max(0,   min(100, humidity)),    2),
        "pressure":    round(max(950, min(1060, pressure)),   2),
        "light_level": min(2000, light_level),
    }


def insert_reading(conn, data: dict):
    sql = """
        INSERT INTO sensor_readings
            (temperature, humidity, pressure, light_level)
        VALUES
            (%(temperature)s, %(humidity)s, %(pressure)s, %(light_level)s)
    """
    with conn.cursor() as cur:
        cur.execute(sql, data)
    conn.commit()


def signal_handler(sig, frame):
    global running
    print("\n[INFO] 종료 신호 수신 - 인젝터 중단 중...")
    running = False


def main():
    global running
    signal.signal(signal.SIGINT,  signal_handler)
    signal.signal(signal.SIGTERM, signal_handler)

    print("=" * 55)
    print("  센서 데이터 인젝터 시작")
    print(f"  DB   : {DB_CONFIG['host']}:{DB_CONFIG['port']}/{DB_CONFIG['database']}")
    print(f"  간격 : {INTERVAL}초")
    print("  종료 : Ctrl+C")
    print("=" * 55)

    conn = None
    tick = 0

    while running:
        try:
            if conn is None or not conn.open:
                conn = get_connection()
                print(f"[INFO] DB 연결 성공")

            data = generate_sensor_data(tick)
            insert_reading(conn, data)

            now = datetime.now().strftime("%H:%M:%S")
            print(
                f"[{now}] tick={tick:05d} | "
                f"온도={data['temperature']:6.2f}°C  "
                f"습도={data['humidity']:5.2f}%  "
                f"기압={data['pressure']:7.2f}hPa  "
                f"조도={data['light_level']:4d}lux"
            )

        except pymysql.err.OperationalError as e:
            print(f"[WARN] DB 오류 (재연결 시도): {e}")
            if conn:
                try:
                    conn.close()
                except Exception:
                    pass
            conn = None
            time.sleep(3)
            continue

        except Exception as e:
            print(f"[ERROR] 예기치 않은 오류: {e}")

        tick += 1
        time.sleep(INTERVAL)

    if conn and conn.open:
        conn.close()

    print("[INFO] 인젝터 종료 완료")


if __name__ == "__main__":
    main()
