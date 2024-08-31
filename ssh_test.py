# -*- coding: utf-8 -*-
import paramiko
import sqlite3
from datetime import datetime, timedelta
import logging
import os

logging.basicConfig(filename='ssh_test.log', level=logging.INFO,
                    format='%(asctime)s - %(levelname)s - %(message)s')

def connect_ssh(hostname, username, password, port=22):
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(hostname, port, username, password, timeout=10)
        return True
    except Exception as e:
        logging.error(f"Failed to connect to {hostname}: {str(e)}")
        return False
    finally:
        client.close()

def update_database(conn, cursor, ssh_id, success):
    current_time = datetime.now()
    
    if success:
        cursor.execute("""
            UPDATE ssh_connections
            SET last_success = ?, failure_count = 0
            WHERE id = ?
        """, (current_time, ssh_id))
    else:
        cursor.execute("""
            UPDATE ssh_connections
            SET last_failure = ?, failure_count = failure_count + 1
            WHERE id = ?
        """, (current_time, ssh_id))
    
    conn.commit()

def initialize_database():
    db_path = 'public/ssh.db'
    
    os.makedirs(os.path.dirname(db_path), exist_ok=True)
    
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    cursor.execute('''
        CREATE TABLE IF NOT EXISTS ssh_connections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            hostname TEXT NOT NULL,
            username TEXT NOT NULL,
            password TEXT NOT NULL,
            port INTEGER DEFAULT 22,
            added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_success DATETIME,
            last_failure DATETIME,
            failure_count INTEGER DEFAULT 0
        )
    ''')

    cursor.execute('''
        CREATE TABLE IF NOT EXISTS log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            level TEXT,
            message TEXT
        )
    ''')

    conn.commit()
    return conn, cursor

def main():
    conn, cursor = initialize_database()

    try:
        cursor.execute("""
            SELECT id, hostname, username, password, port, added_date
            FROM ssh_connections
        """)
        ssh_connections = cursor.fetchall()

        for ssh_id, hostname, username, password, port, added_date in ssh_connections:
            success = connect_ssh(hostname, username, password, port)
            update_database(conn, cursor, ssh_id, success)

            added_date = datetime.strptime(added_date, '%Y-%m-%d %H:%M:%S')
            days_alive = (datetime.now() - added_date).days

            status = "Success" if success else "Failure"
            log_message = f"SSH connection to {hostname}: {status}. Days alive: {days_alive}"
            logging.info(log_message)

            cursor.execute("""
                INSERT INTO log (level, message)
                VALUES (?, ?)
            """, ('INFO', log_message))
            conn.commit()

    except Exception as e:
        error_message = f"An error occurred: {str(e)}"
        logging.error(error_message)
        cursor.execute("""
            INSERT INTO log (level, message)
            VALUES (?, ?)
        """, ('ERROR', error_message))
        conn.commit()
    finally:
        conn.close()

if __name__ == "__main__":
    main()
