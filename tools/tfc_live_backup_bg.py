#!/usr/bin/env python3
from __future__ import annotations
import gzip, hashlib, json, os, secrets, shutil, tarfile, time, urllib.parse, urllib.request, urllib.error
from datetime import datetime
from pathlib import Path

PROJECT=Path('/home/michael/projects/thefinalchapter')
DEPLOY=Path('/home/michael/tfc-deploy')
AUTO=DEPLOY/'auto-deploy'
BACKUP_ROOT=Path('/home/michael/tfc-backups')
LIVE='https://thefinalchapter.press'
ENDPOINT='/tmp/tfc_live_backup_bg_20260818.php'
PHP_REL='tmp/tfc_live_backup_bg_20260818.php'

def sha(p:Path)->str:
    h=hashlib.sha256()
    with p.open('rb') as f:
        for b in iter(lambda:f.read(1024*1024), b''): h.update(b)
    return h.hexdigest()

def pkg(name:str, php:str):
    d=DEPLOY/name
    if d.exists(): shutil.rmtree(d)
    f=d/'payload'/PHP_REL; f.parent.mkdir(parents=True); f.write_text(php,encoding='utf-8'); f.chmod(0o644)
    t=DEPLOY/f'{name}.tar.gz'
    if t.exists(): t.unlink()
    with tarfile.open(t,'w:gz') as tf: tf.add(d/'payload',arcname='payload')
    s=sha(t); AUTO.mkdir(parents=True,exist_ok=True); shutil.copy2(t,AUTO/t.name)
    (AUTO/'manifest.txt').write_text(f'package={t.name}\nsha256={s}\nnote={name}\n',encoding='utf-8')
    print(f'package={t.name} sha256={s}')

def get(url, timeout=60):
    req=urllib.request.Request(url,headers={'User-Agent':'TFCBackupBG/1.0'})
    try:
        with urllib.request.urlopen(req,timeout=timeout) as r: return r.status,r.read()
    except urllib.error.HTTPError as e: return e.code,e.read()

def wait_ready(token, timeout=180):
    end=time.time()+timeout
    while time.time()<end:
        st,b=get(f'{LIVE}{ENDPOINT}?'+urllib.parse.urlencode({'token':token,'action':'ping','cb':time.time()}),20)
        if st==200 and b'OK PING' in b: return
        time.sleep(5)
    raise RuntimeError('endpoint not ready')

def download(token, remote, target):
    url=f'{LIVE}{ENDPOINT}?'+urllib.parse.urlencode({'token':token,'action':'download','file':remote})
    req=urllib.request.Request(url,headers={'User-Agent':'TFCBackupBG/1.0'})
    with urllib.request.urlopen(req,timeout=600) as r, target.open('wb') as out:
        while True:
            b=r.read(1024*1024)
            if not b: break
            out.write(b)
    if target.stat().st_size==0: raise RuntimeError(f'empty download {remote}')

def endpoint_php(token):
    return r'''<?php
$token = '__TOKEN__';
$base = sys_get_temp_dir() . '/tfc_live_backup_bg_current';
$statusFile = $base . '/status.json';
$logFile = $base . '/worker.log';
function out_json($x){ header('Content-Type: application/json; charset=UTF-8'); echo json_encode($x, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); exit; }
function rrmdir_bg($dir){ if(!is_dir($dir)) return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $f){ $f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname()); } @rmdir($dir); }
function ident_bg($n){ return '`'.str_replace('`','``',$n).'`'; }
function write_status_bg($base,$arr){ @file_put_contents($base.'/status.json', json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }
function dump_db_bg($target){ require_once dirname(__DIR__).'/includes/config.php'; require_once dirname(__DIR__).'/includes/db.php'; $pdo=getDB(); $gz=gzopen($target,'wb9'); if(!$gz) throw new RuntimeException('open db dump failed'); gzwrite($gz,"-- TFC live database backup\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n"); $tables=$pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM); foreach($tables as $row){ $table=(string)$row[0]; $q=ident_bg($table); gzwrite($gz,"\nDROP TABLE IF EXISTS $q;\n"); $cr=$pdo->query('SHOW CREATE TABLE '.$q)->fetch(PDO::FETCH_NUM); gzwrite($gz,$cr[1].";\n"); $stmt=$pdo->query('SELECT * FROM '.$q, PDO::FETCH_ASSOC); while($rec=$stmt->fetch(PDO::FETCH_ASSOC)){ $cols=array_map('ident_bg', array_keys($rec)); $vals=[]; foreach($rec as $v){ $vals[]=$v===null?'NULL':$pdo->quote((string)$v); } gzwrite($gz,'INSERT INTO '.$q.' ('.implode(',',$cols).') VALUES ('.implode(',',$vals).');' . "\n"); } } gzwrite($gz,"SET FOREIGN_KEY_CHECKS=1;\n"); gzclose($gz); }
function run_worker_bg($base){ if(!is_dir($base) && !mkdir($base,0700,true)) throw new RuntimeException('mkdir failed'); write_status_bg($base,['state'=>'running','started_at'=>gmdate('c')]); dump_db_bg($base.'/database.sql.gz'); $uploads=dirname(__DIR__).'/assets/img/uploads'; if(is_dir($uploads)){ $cmd='tar -czf '.escapeshellarg($base.'/uploads.tar.gz').' -C '.escapeshellarg(dirname(__DIR__).'/assets/img').' uploads'; exec($cmd.' 2>&1',$o,$rc); if($rc!==0) throw new RuntimeException('uploads tar failed'); } $files=[]; foreach(['database.sql.gz','uploads.tar.gz'] as $n){ $p=$base.'/'.$n; if(is_file($p)) $files[$n]=['size'=>filesize($p),'sha256'=>hash_file('sha256',$p)]; } write_status_bg($base,['state'=>'done','finished_at'=>gmdate('c'),'files'=>$files]); }
if (PHP_SAPI === 'cli' && ($argv[1] ?? '') === 'worker') { try { run_worker_bg($base); exit(0); } catch(Throwable $e){ if(!is_dir($base)) @mkdir($base,0700,true); write_status_bg($base,['state'=>'error','message'=>$e->getMessage(),'finished_at'=>gmdate('c')]); fwrite(STDERR,$e->getMessage()."\n"); exit(1); } }
if(!isset($_GET['token']) || !hash_equals($token,(string)$_GET['token'])){ http_response_code(403); header('Content-Type:text/plain'); echo "FORBIDDEN\n"; exit; }
$a=(string)($_GET['action']??'ping');
if($a==='ping'){ header('Content-Type:text/plain'); echo "OK PING\n"; exit; }
if($a==='start'){ rrmdir_bg($base); mkdir($base,0700,true); write_status_bg($base,['state'=>'starting','started_at'=>gmdate('c')]); $cmd='nohup '.escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' worker > '.escapeshellarg($logFile).' 2>&1 & echo $!'; exec($cmd,$out,$rc); out_json(['ok'=>$rc===0,'pid'=>$out[0]??null]); }
if($a==='status'){ if(is_file($statusFile)){ header('Content-Type:application/json'); readfile($statusFile); exit; } out_json(['state'=>'missing']); }
if($a==='download'){ $f=basename((string)($_GET['file']??'')); if(!in_array($f,['database.sql.gz','uploads.tar.gz','status.json','worker.log'],true)){ http_response_code(400); echo 'BAD FILE'; exit; } $p=$base.'/'.$f; if(!is_file($p)){ http_response_code(404); echo 'NOT FOUND'; exit; } header('Content-Type: application/octet-stream'); header('Content-Length: '.filesize($p)); header('Content-Disposition: attachment; filename="'.$f.'"'); readfile($p); exit; }
if($a==='cleanup'){ rrmdir_bg($base); header('Content-Type:text/plain'); echo "OK CLEANUP\n"; exit; }
http_response_code(400); echo "BAD ACTION\n";
'''.replace('__TOKEN__', token)

def gone_php(): return "<?php\nhttp_response_code(410);\nheader('Content-Type: text/plain; charset=UTF-8');\necho \"GONE\\n\";\n"

def main():
    ts=datetime.now().strftime('%Y%m%d-%H%M%S'); token=secrets.token_urlsafe(32); out=BACKUP_ROOT/ts; out.mkdir(parents=True,exist_ok=True); os.chmod(out,0o700); print('backup_dir='+str(out))
    pkg('tfc-live-backup-bg-endpoint-'+ts, endpoint_php(token)); wait_ready(token)
    st,b=get(f'{LIVE}{ENDPOINT}?'+urllib.parse.urlencode({'token':token,'action':'start'}),30); print('start',st,b[:120])
    deadline=time.time()+900
    status={}
    while time.time()<deadline:
        st,b=get(f'{LIVE}{ENDPOINT}?'+urllib.parse.urlencode({'token':token,'action':'status','cb':time.time()}),30)
        if st==200:
            status=json.loads(b.decode()); print('state='+status.get('state','?'))
            if status.get('state') in ('done','error'): break
        time.sleep(10)
    if status.get('state')!='done': raise RuntimeError('backup did not finish: '+repr(status))
    (out/'manifest.json').write_text(json.dumps(status,indent=2),encoding='utf-8')
    for f,meta in status.get('files',{}).items(): download(token,f,out/f); print(f'{f} size={(out/f).stat().st_size} sha256={sha(out/f)} expected={meta.get("sha256")}')
    cfg=PROJECT/'includes/config.php'
    if cfg.is_file(): shutil.copy2(cfg,out/'local-config.php'); os.chmod(out/'local-config.php',0o600); print('local-config.php backed up mode=600')
    sums=[]
    for p in sorted(out.iterdir()):
        if p.is_file() and p.name!='SHA256SUMS': sums.append(f'{sha(p)}  {p.name}')
    (out/'SHA256SUMS').write_text('\n'.join(sums)+'\n',encoding='utf-8'); os.chmod(out/'SHA256SUMS',0o600)
    get(f'{LIVE}{ENDPOINT}?'+urllib.parse.urlencode({'token':token,'action':'cleanup'}),60)
    pkg('tfc-live-backup-bg-cleanup-'+ts, gone_php())
    for _ in range(40):
        st,b=get(f'{LIVE}{ENDPOINT}?cb={time.time()}',20)
        if st==410: print('endpoint=410_GONE'); break
        time.sleep(5)
    else: raise RuntimeError('endpoint not neutralized')
    print('OK_BACKUP_COMPLETE')
if __name__=='__main__': main()
