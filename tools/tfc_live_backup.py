#!/usr/bin/env python3
"""Create a token-protected live TFC backup via the existing auto-deploy runner.

Backs up:
- live MySQL data as SQL gzip generated through the application PDO connection
- live uploaded media directory assets/img/uploads as tar.gz, if present

The temporary public PHP endpoint is token-protected and neutralized to 410 after use.
No credentials are printed or stored by this script.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import gzip
import os
import secrets
import shutil
import tarfile
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime
from pathlib import Path

PROJECT = Path('/home/michael/projects/thefinalchapter')
DEPLOY_ROOT = Path('/home/michael/tfc-deploy')
AUTO = DEPLOY_ROOT / 'auto-deploy'
BACKUP_ROOT = Path('/home/michael/tfc-backups')
LIVE_BASE = 'https://thefinalchapter.press'
TMP_ENDPOINT = '/tmp/tfc_live_backup_20260818.php'
PHP_PATH = 'tmp/tfc_live_backup_20260818.php'


def sha256_file(path: Path) -> str:
    h = hashlib.sha256()
    with path.open('rb') as fh:
        for chunk in iter(lambda: fh.read(1024 * 1024), b''):
            h.update(chunk)
    return h.hexdigest()


def write_package(name: str, files: dict[str, str]) -> tuple[Path, str]:
    pkg_dir = DEPLOY_ROOT / name
    if pkg_dir.exists():
        shutil.rmtree(pkg_dir)
    payload = pkg_dir / 'payload'
    payload.mkdir(parents=True)
    for rel, content in files.items():
        target = payload / rel
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(content, encoding='utf-8')
        if rel.endswith('.php'):
            target.chmod(0o644)
    tar_path = DEPLOY_ROOT / f'{name}.tar.gz'
    if tar_path.exists():
        tar_path.unlink()
    with tarfile.open(tar_path, 'w:gz') as tf:
        tf.add(payload, arcname='payload')
    digest = sha256_file(tar_path)
    AUTO.mkdir(parents=True, exist_ok=True)
    shutil.copy2(tar_path, AUTO / tar_path.name)
    (AUTO / 'manifest.txt').write_text(
        f'package={tar_path.name}\nsha256={digest}\nnote={name}\n',
        encoding='utf-8',
    )
    return tar_path, digest


def http_get(url: str, timeout: int = 60) -> tuple[int, bytes]:
    req = urllib.request.Request(url, headers={'User-Agent': 'TFCBackup/1.0'})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return int(resp.status), resp.read()
    except urllib.error.HTTPError as exc:
        return int(exc.code), exc.read()


def wait_for_endpoint(token: str, timeout: int = 180) -> None:
    deadline = time.time() + timeout
    url = f'{LIVE_BASE}{TMP_ENDPOINT}?token={urllib.parse.quote(token)}&action=ping&cb={int(time.time())}'
    last = None
    while time.time() < deadline:
        status, body = http_get(url, timeout=20)
        last = (status, body[:160])
        if status == 200 and b'OK BACKUP_PING' in body:
            return
        time.sleep(5)
    raise RuntimeError(f'backup endpoint not ready; last={last!r}')


def download_stream(token: str, action: str, local_path: Path) -> None:
    query = urllib.parse.urlencode({'token': token, 'action': action})
    url = f'{LIVE_BASE}{TMP_ENDPOINT}?{query}'
    req = urllib.request.Request(url, headers={'User-Agent': 'TFCBackup/1.0'})
    with urllib.request.urlopen(req, timeout=600) as resp, local_path.open('wb') as out:
        if int(resp.status) != 200:
            raise RuntimeError(f'{action} failed with HTTP {resp.status}')
        while True:
            chunk = resp.read(1024 * 1024)
            if not chunk:
                break
            out.write(chunk)
    if local_path.stat().st_size == 0:
        raise RuntimeError(f'{action} produced empty file')


def download_file(token: str, remote_name: str, local_path: Path, expected_sha: str) -> None:
    query = urllib.parse.urlencode({'token': token, 'action': 'download', 'file': remote_name})
    url = f'{LIVE_BASE}{TMP_ENDPOINT}?{query}'
    req = urllib.request.Request(url, headers={'User-Agent': 'TFCBackup/1.0'})
    with urllib.request.urlopen(req, timeout=240) as resp, local_path.open('wb') as out:
        if int(resp.status) != 200:
            raise RuntimeError(f'download {remote_name} failed with HTTP {resp.status}')
        while True:
            chunk = resp.read(1024 * 1024)
            if not chunk:
                break
            out.write(chunk)
    got = sha256_file(local_path)
    if got != expected_sha:
        local_path.unlink(missing_ok=True)
        raise RuntimeError(f'sha mismatch for {remote_name}: expected {expected_sha}, got {got}')


def php_backup_script(token: str) -> str:
    # Token is generated per run and deployed only in this temporary script.
    return f'''<?php
$token = {json.dumps(token)};
if (!isset($_GET['token']) || !hash_equals($token, (string)$_GET['token'])) {{
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "FORBIDDEN\n";
    exit;
}}
$action = (string)($_GET['action'] ?? 'ping');
$root = dirname(__DIR__);
$backupBase = sys_get_temp_dir() . '/tfc_live_backup_current';
$manifestPath = $backupBase . '/manifest.json';

function fail_backup(string $msg, int $code = 500): void {{
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "ERROR " . $msg . "\n";
    exit;
}}
function rrmdir_backup(string $dir): void {{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {{ $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }}
    @rmdir($dir);
}}
function sql_ident_backup(string $name): string {{ return '`' . str_replace('`', '``', $name) . '`'; }}
function gzwrite_line_backup($gz, string $line): void {{ if (gzwrite($gz, $line . "\n") === false) fail_backup('gzwrite failed'); }}
function dump_db_backup(string $target): void {{
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/db.php';
    $pdo = getDB();
    $gz = gzopen($target, 'wb9');
    if (!$gz) fail_backup('cannot open db dump');
    gzwrite_line_backup($gz, '-- TFC live database backup');
    gzwrite_line_backup($gz, 'SET NAMES utf8mb4;');
    gzwrite_line_backup($gz, 'SET FOREIGN_KEY_CHECKS=0;');
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $row) {{
        $table = (string)$row[0];
        $quoted = sql_ident_backup($table);
        gzwrite_line_backup($gz, "\nDROP TABLE IF EXISTS " . $quoted . ';');
        $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
        gzwrite_line_backup($gz, $create[1] . ';');
        $stmt = $pdo->query('SELECT * FROM ' . $quoted, PDO::FETCH_ASSOC);
        while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {{
            $cols = array_map('sql_ident_backup', array_keys($record));
            $vals = [];
            foreach ($record as $value) {{
                $vals[] = $value === null ? 'NULL' : $pdo->quote((string)$value);
            }}
            gzwrite_line_backup($gz, 'INSERT INTO ' . $quoted . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ');');
        }}
    }}
    gzwrite_line_backup($gz, 'SET FOREIGN_KEY_CHECKS=1;');
    gzclose($gz);
}}
function dump_db_plain_backup($out): void {{
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/db.php';
    $pdo = getDB();
    $write = function (string $line) use ($out): void {{ fwrite($out, $line . "\n"); }};
    $write('-- TFC live database backup');
    $write('SET NAMES utf8mb4;');
    $write('SET FOREIGN_KEY_CHECKS=0;');
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $row) {{
        $table = (string)$row[0];
        $quoted = sql_ident_backup($table);
        $write("\nDROP TABLE IF EXISTS " . $quoted . ';');
        $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
        $write($create[1] . ';');
        $stmt = $pdo->query('SELECT * FROM ' . $quoted, PDO::FETCH_ASSOC);
        while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {{
            $cols = array_map('sql_ident_backup', array_keys($record));
            $vals = [];
            foreach ($record as $value) {{ $vals[] = $value === null ? 'NULL' : $pdo->quote((string)$value); }}
            $write('INSERT INTO ' . $quoted . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ');');
        }}
        if (function_exists('flush')) @flush();
    }}
    $write('SET FOREIGN_KEY_CHECKS=1;');
}}
function make_manifest_backup(string $base): array {{
    $files = [];
    foreach (['db.sql.gz', 'uploads.tar.gz'] as $name) {{
        $path = $base . '/' . $name;
        if (is_file($path)) {{
            $files[$name] = ['size' => filesize($path), 'sha256' => hash_file('sha256', $path)];
        }}
    }}
    return ['created_at' => gmdate('c'), 'files' => $files];
}}

if ($action === 'ping') {{
    header('Content-Type: text/plain; charset=UTF-8');
    echo "OK BACKUP_PING\n";
    exit;
}}
if ($action === 'download_db_plain') {{
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="database.sql"');
    $out = fopen('php://output', 'wb');
    if (!$out) fail_backup('cannot open output');
    dump_db_plain_backup($out);
    fclose($out);
    exit;
}}
if ($action === 'download_db') {{
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="database.sql.gz"');
    dump_db_backup('php://output');
    exit;
}}
if ($action === 'download_uploads') {{
    $uploads = dirname(__DIR__) . '/assets/img/uploads';
    if (!is_dir($uploads)) fail_backup('uploads not found', 404);
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="uploads.tar.gz"');
    $cmd = 'tar -czf - -C ' . escapeshellarg(dirname(__DIR__) . '/assets/img') . ' uploads';
    passthru($cmd, $rc);
    if ($rc !== 0) fail_backup('uploads tar failed');
    exit;
}}
if ($action === 'create_db') {{
    rrmdir_backup($backupBase);
    if (!mkdir($backupBase, 0700, true) && !is_dir($backupBase)) fail_backup('cannot create backup dir');
    dump_db_backup($backupBase . '/db.sql.gz');
    $manifest = make_manifest_backup($backupBase);
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}}
if ($action === 'create_uploads') {{
    if (!is_dir($backupBase) && !mkdir($backupBase, 0700, true)) fail_backup('cannot create backup dir');
    $uploads = dirname(__DIR__) . '/assets/img/uploads';
    if (is_dir($uploads)) {{
        $cmd = 'tar -czf ' . escapeshellarg($backupBase . '/uploads.tar.gz') . ' -C ' . escapeshellarg(dirname(__DIR__) . '/assets/img') . ' uploads';
        exec($cmd . ' 2>&1', $out, $rc);
        if ($rc !== 0) fail_backup('uploads tar failed');
    }}
    $manifest = make_manifest_backup($backupBase);
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}}
if ($action === 'download') {{
    $file = basename((string)($_GET['file'] ?? ''));
    if (!in_array($file, ['manifest.json', 'db.sql.gz', 'uploads.tar.gz'], true)) fail_backup('invalid file', 400);
    $path = $backupBase . '/' . $file;
    if (!is_file($path)) fail_backup('not found', 404);
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $file . '"');
    readfile($path);
    exit;
}}
if ($action === 'cleanup') {{
    rrmdir_backup($backupBase);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "OK BACKUP_CLEANUP\n";
    exit;
}}
fail_backup('unknown action', 400);
'''


def php_gone_script() -> str:
    return '''<?php
http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "GONE\n";
'''


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument('--keep-remote-artifacts', action='store_true', help='do not call cleanup action before neutralizing endpoint')
    parser.add_argument('--wait-timeout', type=int, default=180)
    args = parser.parse_args()

    ts = datetime.now().strftime('%Y%m%d-%H%M%S')
    out_dir = BACKUP_ROOT / ts
    out_dir.mkdir(parents=True, exist_ok=True)
    os.chmod(out_dir, 0o700)

    token = secrets.token_urlsafe(32)
    deploy_name = f'tfc-live-backup-endpoint-{ts}'
    cleanup_name = f'tfc-live-backup-endpoint-cleanup-{ts}'

    print(f'backup_dir={out_dir}')
    tar_path, digest = write_package(deploy_name, {PHP_PATH: php_backup_script(token)})
    print(f'endpoint_package={tar_path.name} sha256={digest}')
    wait_for_endpoint(token, args.wait_timeout)
    print('endpoint=ready')

    db_plain = out_dir / 'database.sql'
    download_stream(token, 'download_db_plain', db_plain)
    db_gz = out_dir / 'database.sql.gz'
    with db_plain.open('rb') as src, gzip.open(db_gz, 'wb', compresslevel=9) as dst:
        shutil.copyfileobj(src, dst)
    db_plain.unlink()
    uploaded = [(db_gz.name, db_gz)]

    uploads_path = out_dir / 'uploads.tar.gz'
    download_stream(token, 'download_uploads', uploads_path)
    uploaded.append((uploads_path.name, uploads_path))

    manifest = {'created_at': datetime.utcnow().isoformat(timespec='seconds') + 'Z', 'files': {}}
    for name, local_path in uploaded:
        digest = sha256_file(local_path)
        manifest['files'][local_path.name] = {'size': local_path.stat().st_size, 'sha256': digest}
        print(f'downloaded={local_path.name} size={local_path.stat().st_size} sha256={digest}')
    (out_dir / 'manifest.json').write_text(json.dumps(manifest, indent=2), encoding='utf-8')

    # Copy local config as a root-only local backup if present. Do not print contents.
    local_config = PROJECT / 'includes/config.php'
    if local_config.is_file():
        config_target = out_dir / 'local-config.php'
        shutil.copy2(local_config, config_target)
        os.chmod(config_target, 0o600)
        print(f'local_config_backup={config_target.name} mode=600 sha256={sha256_file(config_target)}')

    checksums = []
    for p in sorted(out_dir.iterdir()):
        if p.is_file() and p.name != 'SHA256SUMS':
            checksums.append(f'{sha256_file(p)}  {p.name}')
    (out_dir / 'SHA256SUMS').write_text('\n'.join(checksums) + '\n', encoding='utf-8')
    os.chmod(out_dir / 'SHA256SUMS', 0o600)

    if not args.keep_remote_artifacts:
        cleanup_url = f'{LIVE_BASE}{TMP_ENDPOINT}?' + urllib.parse.urlencode({'token': token, 'action': 'cleanup'})
        status, body = http_get(cleanup_url, timeout=60)
        print(f'remote_artifacts_cleanup_status={status}')

    gone_tar, gone_sha = write_package(cleanup_name, {PHP_PATH: php_gone_script()})
    print(f'neutralize_package={gone_tar.name} sha256={gone_sha}')
    # Wait until the endpoint returns 410 after runner picks up the neutralizer.
    deadline = time.time() + args.wait_timeout
    last_status = None
    while time.time() < deadline:
        status, body = http_get(f'{LIVE_BASE}{TMP_ENDPOINT}?cb={int(time.time())}', timeout=20)
        last_status = status
        if status == 410:
            print('endpoint=410_GONE')
            break
        time.sleep(5)
    else:
        raise RuntimeError(f'endpoint not neutralized, last_status={last_status}')

    print('OK_BACKUP_COMPLETE')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
