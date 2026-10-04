#!/usr/bin/env python3
"""Package installed production dependencies and the compiled SPA; never copy secrets."""
import argparse,hashlib,json,re,tarfile,time
from pathlib import Path
parser=argparse.ArgumentParser();parser.add_argument('--version',required=True);parser.add_argument('--vendor',type=Path);parser.add_argument('--output',type=Path);args=parser.parse_args()
root=Path(__file__).resolve().parents[1];version=args.version
if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9._-]{0,99}',version):raise SystemExit('Invalid release identifier')
vendor=args.vendor or root/'backend/vendor';out=args.output or root/'artifacts';out.mkdir(exist_ok=True,parents=True)
if not (vendor/'autoload.php').exists():raise SystemExit('Install production Composer dependencies first')
if (vendor/'phpunit').exists():raise SystemExit('Development dependencies detected: use a separate composer install --no-dev vendor directory')
if not (root/'frontend/dist/index.html').exists():raise SystemExit('Build frontend first')
entries={}
for directory in ['app','config','database/migrations','resources','routes','bootstrap']:
 for file in (root/'backend'/directory).rglob('*'):
  if file.is_file() and 'cache' not in file.relative_to(root/'backend').parts:entries['backend/'+file.relative_to(root/'backend').as_posix()]=file
for name in ['artisan','composer.json','composer.lock']:entries['backend/'+name]=root/'backend'/name
for file in vendor.rglob('*'):
 if file.is_file():entries['backend/vendor/'+file.relative_to(vendor).as_posix()]=file
for file in (root/'frontend/dist').rglob('*'):
 if file.is_file():entries['public/'+file.relative_to(root/'frontend/dist').as_posix()]=file
for name in ['backup-db.php','validate-env.php']:entries['backend/scripts/'+name]=root/'scripts'/name
entries['backend/public/.htaccess']=root/'backend/public/.htaccess'
entries['public/backend/.htaccess']=root/'backend/public/.htaccess'
entries['public/.htaccess']=root/'ops/spa.htaccess'
entries['public/backend/index.php']=root/'ops/public-index.php'
entries['ops/deploy.sh']=root/'ops/deploy.sh';entries['ops/rollback.sh']=root/'ops/rollback.sh'
archive=out/(version+'.tar.gz')
metadata=json.dumps({'version':version,'built_at':int(time.time()),'php':'8.3','frontend_api':'/backend/api'},indent=2).encode()
with tarfile.open(archive,'w:gz') as tar:
 for name,file in sorted(entries.items()):
  if file.is_symlink():raise SystemExit('Unexpected symlink: '+str(file))
  tar.add(file,arcname=name,recursive=False)
 import io
 info=tarfile.TarInfo('public/release.json');info.size=len(metadata);info.mode=0o644;tar.addfile(info,io.BytesIO(metadata))
checksum=hashlib.sha256(archive.read_bytes()).hexdigest()
(archive.with_suffix(archive.suffix+'.sha256')).write_text(checksum+'  '+archive.name+'\n')
print(archive)
