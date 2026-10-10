#!/usr/bin/env python3
"""Cumulative existing-installation update: no dependencies, secrets or customer data."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import hashlib,json,datetime
root=Path(__file__).resolve().parents[1];out=root/'deployment';out.mkdir(exist_ok=True)
backend=out/'invoice-numbering-backend-update.zip';frontend=out/'invoice-numbering-frontend-update.zip'
with ZipFile(backend,'w',ZIP_DEFLATED) as z:
 for directory in ['app','config','database/migrations','resources','routes','bootstrap']:
  for f in sorted((root/'backend'/directory).rglob('*')):
   if f.is_file() and 'cache' not in f.relative_to(root/'backend').parts:z.write(f,f.relative_to(root/'backend'))
 for name in ['artisan','composer.json','composer.lock','public/.htaccess']:z.write(root/'backend'/name,name)
 z.write(root/'docs/NUMBERING-PO-UPDATE.md','NUMBERING-PO-UPDATE.md')
with ZipFile(frontend,'w',ZIP_DEFLATED) as z:
 for f in sorted((root/'frontend/dist').rglob('*')):
  if f.is_file() and f.name!='.htaccess':z.write(f,f.relative_to(root/'frontend/dist'))
 z.write(root/'ops/spa.htaccess','.htaccess')
 z.writestr('release.json',json.dumps({'version':'local-20261010-numbering','built_at':datetime.datetime.now(datetime.timezone.utc).isoformat(),'frontend_api':'/backend/api'}))
for path in [backend,frontend]:
 with ZipFile(path) as z:
  names=z.namelist();assert not any('/vendor/' in n or '/storage/' in n or n=='.env' or n.startswith('/') or '..' in Path(n).parts for n in names)
 path.with_suffix('.zip.sha256').write_text(hashlib.sha256(path.read_bytes()).hexdigest()+'  '+path.name+'\n')
 print(str(path)+' ('+str(len(names))+' files)')
