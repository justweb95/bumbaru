import numpy as np, trimesh, sys
sys.path.insert(0,'.')
from rast import zbuf
m=trimesh.load('base.ply',process=False)
res=0.1
W=int(np.ceil(m.extents[0]/res)); Hh=int(np.ceil(m.extents[1]/res))
H,_=zbuf(m.triangles,0,0,res,W,Hh,mode='max'); H[~np.isfinite(H)]=np.nan
np.save('Hf.npy',H); print(H.shape, np.nanmin(H), np.nanmax(H))
