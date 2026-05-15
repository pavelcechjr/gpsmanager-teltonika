# Teltonika setup guide

This guide covers configuring Teltonika FMC/FMB GPS trackers to communicate with GPSManager.

## Tested hardware

- **FMC003** (OBD II dongle, primary target)
- Should work with: FMB001/010/020/120/125/130/920, FMC130/640, FMM/FMU series — anything speaking Codec 8 / Codec 8 Extended

## Initial setup via SMS

Teltonika units accept configuration via SMS commands. Format depends on whether
the unit has an admin password set:

```
Password configured:    "admin_password command"
No password (default):  "  command"           ← TWO SPACES before command
```

This guide assumes **no password** (factory default).

### Point Teltonika to your server

```
"  setparam 2004:gpsmanager.example.com"    # Server domain (or IP)
"  setparam 2005:5027"                       # Port
"  setparam 2003:0"                          # Protocol: 0=TCP, 1=UDP (use TCP)
"  setparam 2010:0"                          # TLS: 0=plain, 1=TLS (GPSManager uses plain)
"  cpureset"                                 # Restart modem to apply
```

After cpureset (60-90 seconds) the unit will reconnect using new settings.
Listener auto-detects IMEI via Codec 8 handshake and creates a Device entry
on first contact. Assign device to vehicle via web UI: **Vozidla → Edit → Teltonika jednotka**.

### Diagnostic SMS commands

```
"  getstatus"     # Returns: Data Link, GPRS, SIM, Signal, Operator, NetType, …
                  #   Data Link: 1 = connected to your server ✓
                  #   GPRS: 1 = mobile data working ✓
"  getgps"        # Returns: GPS:N Sat:N Lat:.. Long:.. (GPS:3 = fix, GPS:1 = no fix)
"  getver"        # Firmware version + IMEI
"  getrecord"     # Force one AVL packet to server (wake from sleep)
"  getio"         # Current I/O state
"  getparam N"    # Read parameter N (e.g. 2004 = Domain1, 2005 = Port1)
```

## Configurator (USB) — custom PIDs

For advanced telemetry (engine oil temperature, HV battery for hybrids, custom OBD2 PIDs)
you need **Teltonika Configurator** — a free Windows-only desktop tool.

1. Download from https://wiki.teltonika-gps.com/ (search "Configurator" + your model)
2. Connect unit to PC via mini-USB cable (FMC003) or USB-C / RJ45 (other models)
3. Open Configurator — it auto-detects the unit
4. Navigate to **OBD → I/O Parameters → Custom PIDs**
5. Add Mode 0x22 PIDs (see hybrid section below)
6. Save & load to device

### Standard OBD2 PIDs (no Configurator needed)

These are auto-enabled in factory firmware and arrive in `io_data` automatically when ignition is ON:

| AVL ID | OBD PID | Meaning | Unit |
|---|---|---|---|
| 30 | 0x01 | DTC count | count |
| 31 | 0x04 | Engine load | % |
| 32 | 0x05 | Coolant temperature | °C |
| 36 | 0x0C | Engine RPM | rpm |
| 37 | 0x0D | Vehicle speed | km/h |
| 41 | 0x11 | Throttle position | % |
| 42 | 0x1F | Engine run time | s |
| 43 | 0x21 | Distance with MIL on | km |
| 48 | 0x2F | Fuel level | % |
| 57 | 0x3C | Catalyst temperature | °C |
| 256 | — | VIN (mode 0x09) | string |
| 389 | 0x31 | **Total mileage from ECU** | km |

## VAG (Volkswagen / Audi / Škoda / Seat) — manufacturer-specific UDS PIDs

For VW Group hybrids (Golf MK8 eHybrid, Passat GTE, Tiguan eHybrid, Škoda Superb iV, etc.),
you can read **HV battery data** via UDS Mode 0x22:

Add these to Configurator → OBD → I/O Parameters → Custom PIDs:

| Mode | PID | Length | Scale | Meaning | Suggested AVL ID |
|---|---|---|---|---|---|
| 0x22 | 0x028C | 1 | 1 | HV battery SOC | 850 |
| 0x22 | 0x02E5 | 2 | 0.1 | HV voltage | 851 |
| 0x22 | 0x02E0 | 2 | 0.1 (signed) | HV current | 852 |
| 0x22 | 0x02EE | 1 | 1 (signed) | HV temperature | 853 |
| 0x22 | 0x1E1B | 1 | 1 | EV mode flag (0/1) | 854 |
| 0x22 | 0x028D | 1 | 1 | Charging status | 855 |
| 0x22 | 0x1235 | 2 | 0.1 | DC charging power (kW) | 856 |
| 0x22 | 0x117B | 2 | 0.5 | Fuel level (precise liters) | 857 |

After mapping these AVL IDs in Configurator, GPSManager web UI shows a **green "Trakční baterie"
panel** in trip detail with SOC start/end, kWh consumption, EV mode %, HV temp peak.

Exact PIDs may vary by ECU firmware version — verify with **OBDeleven** app
(€25/year + BT ELM327 dongle) or **VCDS** (Ross-Tech).

## Ford manufacturer-specific PIDs

Ford uses different UDS PIDs than VAG. For Ford Transit Custom, Focus, Kuga PHEV, etc.,
contact your Ford dealer or use **Forscan** (free Windows tool) to identify PIDs.
Common Ford-specific PIDs:

| Mode | PID | Meaning |
|---|---|---|
| 0x22 | 0xF40C | Engine oil temperature |
| 0x22 | 0xDD05 | HV battery SOC (Kuga PHEV) |
| 0x22 | 0xF441 | Fuel rail pressure |

## Toyota / Hyundai / Kia

These use yet different UDS PIDs. Both groups have active community databases:
- Toyota Prius / RAV4 PHEV: try [PriusChat forum](https://priuschat.com/) PID lists
- Hyundai / Kia: [evnotify community](https://github.com/EVNotify/EVNotify) has comprehensive PIDs

## Troubleshooting

### `getstatus` returns `Data Link: 0` — unit not reaching server

1. Check firewall — TCP port 5027 must be open to public internet on your server
2. Check DNS — `dig your-server.com` should resolve to public IP
3. Check listener — `docker compose exec app supervisorctl status` should show
   `teltonika-listener   RUNNING`
4. Try direct IP instead of domain: `setparam 2004:1.2.3.4`

### Listener receives connections but no AVL data

- Check Teltonika is sending Codec 8 (not Codec 12 or other) — `getparam 11001` should return 8 or 142
- Verify TLS not enabled — `getparam 2010` should be 0

### `Data Link: 1` but no positions in DB

- Unit is connected but in sleep (no movement / ignition off). Force a packet: `"  getrecord"`
- Check listener log: `docker compose exec app tail -f /var/log/supervisor/listener.log`

## Reference

- Teltonika Codec 8 spec: https://wiki.teltonika-gps.com/view/Codec
- AVL ID list: https://wiki.teltonika-gps.com/view/AVL_ID
- FMC003 manual: https://wiki.teltonika-gps.com/view/FMC003
