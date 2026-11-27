import re, numpy as np, trimesh, sys
src=sys.argv[1]
txt=open(src+'/3D/3dmodel.model').read()
objs={}
for m in re.finditer(r'<object id="(\d+)" type="model">\s*<mesh>(.*?)</mesh>',txt,re.S):
    oid=int(m.group(1)); body=m.group(2)
    v=np.array(re.findall(r'<vertex x="([^"]+)" y="([^"]+)" z="([^"]+)"',body),float)
    t=np.array(re.findall(r'<triangle v1="(\d+)" v2="(\d+)" v3="(\d+)"',body),int)
    objs[oid]=(v,t)
comps=re.findall(r'<component objectid="(\d+)" transform="([^"]+)"',txt)
item=[float(x) for x in re.search(r'<item objectid="4" transform="([^"]+)"',txt).group(1).split()]
def M(a):
    a=[float(x) for x in a]; m=np.eye(4); m[:3,:3]=np.array(a[:9]).reshape(3,3).T; m[:3,3]=a[9:12]; return m
IT=M(item)
for oid,tr in comps:
    v,t=objs[int(oid)]
    mesh=trimesh.Trimesh(v,t,process=False)
    mesh.apply_transform(IT@M(tr.split()))
    mesh.export(f'obj{oid}.ply')
    print(oid,len(v),len(t),mesh.bounds.round(2).tolist(),'wt',mesh.is_watertight, 'bodies',mesh.body_count if hasattr(mesh,'body_count') else '')
