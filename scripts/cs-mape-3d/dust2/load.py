import re, numpy as np, trimesh, sys
t=open(sys.argv[1]).read()
v=np.array(re.findall(r'<vertex x="([^"]+)" y="([^"]+)" z="([^"]+)"',t),float)
f=np.array(re.findall(r'<triangle v1="(\d+)" v2="(\d+)" v3="(\d+)"',t),int)
m=trimesh.Trimesh(v,f,process=False)
print(m.bounds, m.extents, m.is_watertight, m.volume, len(f))
m.export('orig.ply')
z=v[:,2]
h,e=np.histogram(z,bins=40); 
for a,b in zip(e,h): print(round(a,2),b)
cc=m.split(only_watertight=False); print('components',len(cc)); 
for c in sorted(cc,key=lambda c:-len(c.faces))[:10]: print(c.bounds.round(1).tolist(), len(c.faces))
