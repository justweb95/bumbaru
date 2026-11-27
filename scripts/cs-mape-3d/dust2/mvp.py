import re,numpy as np,trimesh
t=open('sve/3D/Objects/object_4.model').read()
v=np.array(re.findall(r'<vertex x="([^"]+)" y="([^"]+)" z="([^"]+)"',t),float)*9.21167256
f=np.array(re.findall(r'<triangle v1="(\d+)" v2="(\d+)" v3="(\d+)"',t),int)
m=trimesh.Trimesh(v,f); print(m.extents, m.volume/1000, m.is_watertight)
z=np.round(v[:,2],2); u,c=np.unique(z,return_counts=True); print(list(zip(u,c))[:20])
m.export('mvp.ply')
