import numpy as np, trimesh, sys, json
sys.path.insert(0,'.')
from raster import heightmap
m=trimesh.load('obj2.ply',process=False)
b=m.bounds
L=160.0; s=L/(b[1,0]-b[0,0])
V=(m.vertices-[b[0,0],b[0,1],0])*s
V[:,2]=m.vertices[:,2]*s
ms=trimesh.Trimesh(V,m.faces,process=True)
print('scale',s,'bounds',ms.bounds,'wt',ms.is_watertight,'vol',ms.volume/1000)
ms.export('map_scaled.ply')
res=0.1
W=int(np.ceil(ms.bounds[1,0]/res))+1; H=int(np.ceil(ms.bounds[1,1]/res))+1
z,_=heightmap(ms.vertices,ms.faces,0,0,res,W,H)
np.save('hz.npy',z)
# radar->scaled model mm mapping
sx=(210.72/838)*s; sy=(178.29/715)*s
json.dump({'s':s,'ox':105,'oy':157,'sx':sx,'sy':sy,'Hmm':float(ms.bounds[1,1]),'res':res},open('map.json','w'))
print(W,H,sx,sy)
