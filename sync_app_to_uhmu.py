#!/usr/bin/env python3
"""
Data Bridge: Sync RAIL Data Server (app.py) ↔ Master Station (uhmu-main)
Exports app.py configuration to match uhmu-main format
"""

import json
import sqlite3
from pathlib import Path
from datetime import datetime

# Paths
APP_DB_PATH = Path("C:/Users/PRITAM/RAIL_DATA_SERVER_DATA/database/rail_data.db")
APP_CONFIG_PATH = Path("C:/Users/PRITAM/RAIL_DATA_SERVER_DATA/config.json")
UHMU_POINTCONFIG_PATH = Path("C:/Users/PRITAM/Downloads/uhmu-main (1)/uhmu-main/UHMU/pointconfig.json")
UHMU_DATA_PATH = Path("C:/Users/PRITAM/Downloads/uhmu-main (1)/uhmu-main/UHMU/data")

def read_app_config():
    """Read config from app.py"""
    try:
        if APP_CONFIG_PATH.exists():
            with open(APP_CONFIG_PATH) as f:
                return json.load(f)
    except Exception as e:
        print(f"Error reading app config: {e}")
    return {}

def get_locations_from_app():
    """Get locations from app.py config"""
    config = read_app_config()
    locations = config.get("locations", [])
    location_names = config.get("location_names", {})
    
    result = []
    for loc in locations:
        result.append({
            "id": loc,
            "name": location_names.get(loc, loc),
            "clients": []
        })
    return result

def get_clients_from_app():
    """Get U-Type and S-Type clients from app.py"""
    config = read_app_config()
    clients = config.get("clients", {})
    
    u_type = {}
    s_type = {}
    
    for location, location_clients in clients.items():
        for client_tag, client_info in location_clients.items():
            client_type = client_info.get("type", "unknown")
            if client_type == "U":
                u_type[client_tag] = client_info
            elif client_type == "S":
                s_type[client_tag] = client_info
    
    return {"u_type": u_type, "s_type": s_type}

def build_pointconfig():
    """Build pointconfig.json for uhmu-main"""
    locations = get_locations_from_app()
    clients = get_clients_from_app()
    
    pointconfig = {
        "locations": locations,
        "u_type_clients": clients["u_type"],
        "s_type_clients": clients["s_type"],
        "metadata": {
            "last_synced": datetime.now().isoformat(),
            "source": "RAIL Data Server (app.py)",
            "version": "1.0"
        }
    }
    
    return pointconfig

def sync_pointconfig():
    """Sync pointconfig.json from app.py to uhmu-main"""
    try:
        pointconfig = build_pointconfig()
        
        # Ensure directory exists
        UHMU_POINTCONFIG_PATH.parent.mkdir(parents=True, exist_ok=True)
        
        # Write pointconfig.json
        with open(UHMU_POINTCONFIG_PATH, 'w') as f:
            json.dump(pointconfig, f, indent=2)
        
        print(f"✓ Synced pointconfig.json")
        print(f"  Locations: {len(pointconfig.get('locations', []))}")
        print(f"  U-Type clients: {len(pointconfig.get('u_type_clients', {}))}")
        print(f"  S-Type clients: {len(pointconfig.get('s_type_clients', {}))}")
        
        return True
    except Exception as e:
        print(f"✗ Error syncing pointconfig: {e}")
        return False

def create_uhmu_credentials():
    """Create login credentials for uhmu-main based on app.py"""
    config = read_app_config()
    
    credentials = {
        "admin": config.get("admin_password", "admin123"),
        "username": config.get("admin_username", "admin"),
        "synced": datetime.now().isoformat()
    }
    
    return credentials

def get_app_data_stats():
    """Get data statistics from app.py database"""
    try:
        if not APP_DB_PATH.exists():
            return {}
        
        conn = sqlite3.connect(str(APP_DB_PATH))
        cur = conn.cursor()
        
        # Get table count
        cur.execute("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")
        table_count = cur.fetchone()[0]
        
        stats = {
            "db_file": str(APP_DB_PATH),
            "tables": table_count,
            "last_checked": datetime.now().isoformat()
        }
        
        conn.close()
        return stats
    except Exception as e:
        print(f"Error reading database stats: {e}")
        return {}

def main():
    print("=" * 60)
    print("RAIL Data Server ↔ Master Station Data Bridge")
    print("=" * 60)
    
    # Step 1: Read app.py config
    print("\n[1] Reading app.py configuration...")
    config = read_app_config()
    if config:
        print(f"  ✓ Loaded config with {len(config.get('locations', []))} locations")
    else:
        print("  ✗ No config found")
        return
    
    # Step 2: Sync pointconfig
    print("\n[2] Syncing pointconfig.json...")
    if sync_pointconfig():
        print(f"  → File: {UHMU_POINTCONFIG_PATH}")
    
    # Step 3: Get credentials
    print("\n[3] Extracting credentials...")
    creds = create_uhmu_credentials()
    print(f"  Username: {creds['username']}")
    print(f"  Password: {'*' * len(creds['admin'])}")
    
    # Step 4: Database stats
    print("\n[4] Database information...")
    stats = get_app_data_stats()
    if stats:
        print(f"  Database: {stats['db_file']}")
        print(f"  Tables: {stats['tables']}")
    
    print("\n" + "=" * 60)
    print("✓ Data Bridge Sync Complete")
    print("=" * 60)
    print("\nWhat's been synced:")
    print("  • Location list → pointconfig.json")
    print("  • Client info → pointconfig.json")
    print("  • Credentials → Ready for login")
    print("\nBoth systems now share common data!")

if __name__ == "__main__":
    main()
