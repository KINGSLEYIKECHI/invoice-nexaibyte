#!/usr/bin/env python3
import argparse,hashlib,json,tarfile
from pathlib import Path
p=argparse.ArgumentParser();p.add_argument('archive',type=Path);a=p.parse_args().archive
expected=a.with_suffix(a.suffix+'.sha256').read_text().split()[0]
assert hashlib.sha256(a.read_bytes()).hexdigest()==expected,'Checksum mismatch'
with tarfile.open(a) as t:
 names=t.getnames()
 for n in ['backend/public/.htaccess','backend/vendor/autoload.php','public/backend/index.php','public/backend/.htaccess','public/.htaccess','public/index.html','public/release.json','backend/scripts/backup-db.php']:
  assert n in names,'Required file missing: '+n
 assert not any(n.endswith('/.env') or '/storage/' in n or n.startswith('backend/tests/') or n.startswith('backend/vendor/phpunit/') for n in names),'Secret/runtime/dev files in archive'
 assert not any(n.startswith('/') or '..' in Path(n).parts for n in names),'Unsafe archive path'
 metadata=json.load(t.extractfile('public/release.json'))
 assert metadata['frontend_api']=='/backend/api'
 js=[n for n in names if n.startswith('public/assets/index-') and n.endswith('.js')]
 assert len(js)==1 and b'/backend/api' in t.extractfile(js[0]).read(),'Wrong frontend API base'
print('Release checksum, structure and exclusion checks passed')
