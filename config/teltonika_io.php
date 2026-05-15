<?php

/**
 * Teltonika AVL IO ID catalog.
 *
 * Reference: https://wiki.teltonika-gps.com/view/AVL_ID
 * Each entry:
 *   label    — display label in Czech
 *   unit     — display unit (or '')
 *   scale    — multiplier on raw value (e.g. 0.001 to convert mV → V)
 *   category — 'system' | 'obd' | 'hev' | 'gsm' | 'meta'
 *   format   — sprintf-style or callable for value display ('%.2f' etc.); defaults to %s
 *
 * NOTE: For Volkswagen MK8 e-hybrid HV battery you need Teltonika Configurator
 *       → OBD → I/O Parameters → enable manufacturer-specific PIDs (Mode 0x22).
 *       Add IO IDs to this catalog as Teltonika starts reporting them.
 */

return [
    // ── Teltonika system / device ─────────────────────────────────────────
    16  => ['label' => 'Celkový odometr',  'unit' => 'm',  'scale' => 1,      'category' => 'system', 'format' => 'km_from_m'],
    21  => ['label' => 'GSM signál',       'unit' => '/5', 'scale' => 1,      'category' => 'gsm'],
    66  => ['label' => 'Externí napájení', 'unit' => 'V',  'scale' => 0.001,  'category' => 'system', 'format' => '%.2f'],
    67  => ['label' => 'Interní baterie',  'unit' => 'V',  'scale' => 0.001,  'category' => 'system', 'format' => '%.2f'],
    68  => ['label' => 'Proud baterie',    'unit' => 'mA', 'scale' => 1,      'category' => 'system'],
    69  => ['label' => 'GNSS status',      'unit' => '',   'scale' => 1,      'category' => 'gsm'],
    181 => ['label' => 'PDOP',             'unit' => '',   'scale' => 0.1,    'category' => 'gsm',    'format' => '%.1f'],
    182 => ['label' => 'HDOP',             'unit' => '',   'scale' => 0.1,    'category' => 'gsm',    'format' => '%.1f'],
    200 => ['label' => 'Sleep mode',       'unit' => '',   'scale' => 1,      'category' => 'system'],
    239 => ['label' => 'Zapalování',       'unit' => '',   'scale' => 1,      'category' => 'system', 'format' => 'bool'],
    240 => ['label' => 'Pohyb',            'unit' => '',   'scale' => 1,      'category' => 'system', 'format' => 'bool'],
    241 => ['label' => 'GSM operátor',     'unit' => '',   'scale' => 1,      'category' => 'gsm'],

    // ── Standard OBD2 PIDs (SAE J1979) ────────────────────────────────────
    30  => ['label' => 'Chybové kódy',     'unit' => '',   'scale' => 1, 'category' => 'obd'],
    31  => ['label' => 'Zátěž motoru',     'unit' => '%',  'scale' => 1, 'category' => 'obd'],
    32  => ['label' => 'Teplota chladiče', 'unit' => '°C', 'scale' => 1, 'category' => 'obd'],
    33  => ['label' => 'Krátký fuel trim', 'unit' => '%',  'scale' => 1, 'category' => 'obd'],
    34  => ['label' => 'Tlak paliva',      'unit' => 'kPa','scale' => 1, 'category' => 'obd'],
    35  => ['label' => 'MAP',              'unit' => 'kPa','scale' => 1, 'category' => 'obd'],
    36  => ['label' => 'Otáčky motoru',    'unit' => 'rpm','scale' => 1, 'category' => 'obd'],
    37  => ['label' => 'Rychlost (OBD)',   'unit' => 'km/h','scale' => 1, 'category' => 'obd'],
    38  => ['label' => 'Timing advance',   'unit' => '°',  'scale' => 1, 'category' => 'obd'],
    39  => ['label' => 'Teplota nasávání', 'unit' => '°C', 'scale' => 1, 'category' => 'obd'],
    40  => ['label' => 'MAF',              'unit' => 'g/s','scale' => 1, 'category' => 'obd'],
    41  => ['label' => 'Plyn (throttle)',  'unit' => '%',  'scale' => 1, 'category' => 'obd'],
    42  => ['label' => 'Doba běhu motoru', 'unit' => 's',  'scale' => 1, 'category' => 'obd'],
    43  => ['label' => 'Vzdálenost s MIL', 'unit' => 'km', 'scale' => 1, 'category' => 'obd'],
    48  => ['label' => 'Palivo',           'unit' => '%',  'scale' => 1, 'category' => 'obd'],
    49  => ['label' => 'Barometr',         'unit' => 'kPa','scale' => 1, 'category' => 'obd'],
    51  => ['label' => 'Control module V', 'unit' => 'V',  'scale' => 0.001, 'category' => 'obd', 'format' => '%.2f'],
    52  => ['label' => 'Abs. zátěž',       'unit' => '%',  'scale' => 1, 'category' => 'obd'],
    53  => ['label' => 'Cmd ekvivalence',  'unit' => '',   'scale' => 1, 'category' => 'obd'],
    54  => ['label' => 'Relativní throttle','unit' => '%', 'scale' => 1, 'category' => 'obd'],
    57  => ['label' => 'Teplota chladiče 2','unit' => '°C','scale' => 1, 'category' => 'obd'],
    60  => ['label' => 'Distance traveled','unit' => 'km', 'scale' => 1, 'category' => 'obd'],
    70  => ['label' => 'Celkové palivo',   'unit' => 'l',  'scale' => 1, 'category' => 'obd'],
    73  => ['label' => 'Teplota chladiče 3','unit' => '°C','scale' => 1, 'category' => 'obd'],

    // ── Engine oil / Transmission temperatures (zapnout v Teltonika Configurator) ──
    // 92  = Engine Oil Temperature (standard SAE PID 0x5C) — povolit v OBD I/O params
    // 116 = Catalyst Temperature
    // VW MK8 DSG transmission: custom UDS PID Mode 0x22, mapovat na volné IO ID
    92  => ['label' => 'Teplota oleje motoru',  'unit' => '°C', 'scale' => 1, 'category' => 'obd'],
    116 => ['label' => 'Teplota katalyzátoru',  'unit' => '°C', 'scale' => 1, 'category' => 'obd'],
    // Placeholder pro transmission oil temp — Pavel přidá konkrétní ID z VW custom PID
    // 870 => ['label' => 'Teplota oleje převodovky', 'unit' => '°C', 'scale' => 1, 'category' => 'obd'],

    256 => ['label' => 'VIN',              'unit' => '',   'scale' => 1, 'category' => 'obd',  'format' => 'vin'],
    // 389 = Total Mileage z ECU auta (OBD2 PID 0x31, km). Ověřeno na Golf MK8 baseline 94 000 km.
    389 => ['label' => 'Tachometr z auta (OBD)', 'unit' => 'km', 'scale' => 1, 'category' => 'obd'],
    390 => ['label' => 'Total mileage counted',   'unit' => '',   'scale' => 1, 'category' => 'obd'],
    541 => ['label' => 'OBD #541',                'unit' => '',   'scale' => 1, 'category' => 'obd'],
    543 => ['label' => 'OBD #543',                'unit' => '',   'scale' => 1, 'category' => 'obd'],
    544 => ['label' => 'OBD #544',                'unit' => '',   'scale' => 1, 'category' => 'obd'],
    755 => ['label' => 'OBD #755',                'unit' => '',   'scale' => 1, 'category' => 'obd'],

    // ── HEV / EV (zatím prázdné — Teltonika musí Configurator je enable) ──
    // Pavel: tady přidej HV battery IDs až je Teltonika začne posílat.
    // Typicky pro VW eHybrid přes UDS Mode 0x22 (custom PIDs).
    // Example placeholders (replace IDs once you confirm them):
    // 850 => ['label' => 'HV baterie SOC', 'unit' => '%', 'scale' => 1, 'category' => 'hev'],
    // 851 => ['label' => 'HV baterie U',   'unit' => 'V', 'scale' => 0.1, 'category' => 'hev', 'format' => '%.1f'],
    // 852 => ['label' => 'HV baterie I',   'unit' => 'A', 'scale' => 0.1, 'category' => 'hev', 'format' => '%.1f'],
    // 853 => ['label' => 'HV baterie T',   'unit' => '°C','scale' => 1, 'category' => 'hev'],
];
