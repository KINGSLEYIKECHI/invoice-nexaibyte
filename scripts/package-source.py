#!/usr/bin/env python3
from pathlib import Path
import os
from zipfile import ZipFile,ZIP_DEFLATED
root=Path(__file__).resolve().parents[1];out=root/'deployment';out.mkdir(exist_ok=True)
excluded={'.git','node_modules','vendor','dist','deployment','artifacts','.release-backend','.release-test','playwright-report','test-results','__pycache__'}
with ZipFile(out/'invoice-saas-github-ready.zip','w',ZIP_DEFLATED) as z:
 for directory,dirs,files in os.walk(root):
  dirs[:]=[d for d in dirs if d not in excluded]
  for name in files:
   file=Path(directory)/name;parts=file.relative_to(root).parts
   if file.name.startswith('.env') and file.name not in ['.env.example','.env.production.example']:continue
   if file.suffix in ['.sql','.gz','.log','.sqlite','.zip'] or '.sqlite' in file.name:continue
   if parts[0]=='backend' and ('storage' in parts or parts[:3]==('backend','bootstrap','cache')):continue
   z.write(file,file.relative_to(root))
with ZipFile(out/'invoice-ci-server-setup.zip','w',ZIP_DEFLATED) as z:
 for name in ['initialize-hostinger.sh','server-config.example.sh','cron.sh','rollback.sh','public-index.php']:
  z.write(root/'ops'/name,name)
print(out/'invoice-saas-github-ready.zip');print(out/'invoice-ci-server-setup.zip')
