import numpy as np, trimesh, sys
sys.path.insert(0,'.')
from rast import zbuf
from PIL import Image
m=trimesh.load('orig.ply',process=False)
res=0.25
(x0,y0,z0),(x1,y1,z1)=m.bounds
W=int(np.ceil((x1-x0)/res)); Hh=int(np.ceil((y1-y0)/res))
T=m.triangles
H,_=zbuf(T,x0,y0,res,W,Hh,mode='max'); B,_=zbuf(T,x0,y0,res,W,Hh,mode='min')
H[~np.isfinite(H)]=np.nan; B[~np.isfinite(B)]=np.nan
np.save('H.npy',H); np.save('B.npy',B); np.save('grid.npy',np.array([x0,y0,res]))
print('bottom', np.nanpercentile(B,[0,1,50,99,100]))
print('top', np.nanpercentile(H,[0,1,5,25,50,75,95,100]))
def img(A,fn):
    a=(A-np.nanmin(A))/(np.nanmax(A)-np.nanmin(A)); a=np.nan_to_num(a,nan=0)
    Image.fromarray((a[::-1]*255).astype(np.uint8)).save(fn)
img(H,'H.png'); img(B,'B.png')
