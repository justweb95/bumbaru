import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import zipfile,re,numpy as np,trimesh, xml.dom.minidom as md
from manifold3d import Manifold, Mesh
OUT='/mnt/e/BUMBARU 3D Stampa/sve-za-cs-mape-proizvod/gotovo/3mf/'
for n in sys.argv[1:]:
    z=zipfile.ZipFile(OUT+f'{n}.3mf')
    for f in z.namelist():
        if f.endswith(('.model','.config','.rels')) and 'project_settings' not in f: md.parseString(z.read(f))
    t=z.read('3D/Objects/object_1.model').decode()
    ms=[];tot=0;zmin=1e9
    for oid,body in re.findall(r'<object id="(\d+)"[^>]*>(.*?)</object>',t,re.S):
        V=np.array(re.findall(r'x="([^"]+)" y="([^"]+)" z="([^"]+)"',body),float)
        F=np.array(re.findall(r'v1="(\d+)" v2="(\d+)" v3="(\d+)"',body),int)
        tm=trimesh.Trimesh(V,F,process=False); zmin=min(zmin,V[:,2].min())
        m=Manifold(Mesh(vert_properties=V.astype(np.float32),tri_verts=F.astype(np.uint32)))
        ms.append(m); tot+=m.volume()
        if not tm.is_watertight: print('  part',oid,'NOT watertight')
    u=Manifold()
    for m in ms: u=u+m
    print(n,'parts',len(ms),'sum vol',round(tot/1000,3),'union',round(u.volume()/1000,3),'zmin',round(zmin,4),'genus/components', u.num_vert()>0, len(u.decompose()))
