import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import zipfile,re,numpy as np
from render import render, render_flat
OUT='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/'
COL={'#E6CBA0':1,'#C8102E':2,'#1B2A5C':3,'#2F5233':4,'#6B4226':5,'#F4F1EA':6}
import json
for n in sys.argv[1:]:
    z=zipfile.ZipFile(OUT+f'3mf/{n}.3mf')
    cols=json.loads(z.read('Metadata/project_settings.config'))['filament_colour']
    ms=z.read('Metadata/model_settings.config').decode()
    ext={int(a):int(b) for a,b in re.findall(r'<part id="(\d+)"[^>]*>.*?key="extruder" value="(\d+)"',ms,re.S)}
    t=z.read('3D/Objects/object_1.model').decode()
    parts=[]
    for oid,body in re.findall(r'<object id="(\d+)"[^>]*>(.*?)</object>',t,re.S):
        V=np.array(re.findall(r'x="([^"]+)" y="([^"]+)" z="([^"]+)"',body),float)
        F=np.array(re.findall(r'v1="(\d+)" v2="(\d+)" v3="(\d+)"',body),int)
        parts.append((V,F,COL[cols[ext[int(oid)]-1]]))
    print(n,render_flat(parts,OUT+f'pregled/{n}.png'))
