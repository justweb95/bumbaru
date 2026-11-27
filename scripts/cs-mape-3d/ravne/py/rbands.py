import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np
from shapely.geometry import Polygon
from shapely.ops import unary_union
from skimage import measure
from zbuf import zbuffer
def mask_poly(mask,res,ox=0,oy=0):
    H,W=mask.shape
    m=np.pad(mask,1).astype(float)
    cs=measure.find_contours(m,0.5)
    ps=[]
    for c in cs:
        if len(c)<4: continue
        # c rows/cols in padded coords; row0=max y
        x=(c[:,1]-1+0.5)*res+ox; y=(H-(c[:,0]-1+0.5))*res+oy
        p=Polygon(np.c_[x,y]).buffer(0)
        if p.area>res*res*2: ps.append(p.simplify(res*0.6))
    res_=Polygon()
    for p in sorted(ps,key=lambda q:-q.area):
        res_=res_.symmetric_difference(p)
    return res_.buffer(0)
def raster_bands(V,F,res=0.05):
    Z=zbuffer(V,F,res)
    zs=np.unique(np.round(V[:,2],3))
    out=[]
    for a,b in zip(zs[:-1],zs[1:]):
        mk=np.nan_to_num(Z,nan=-1)>(a+b)/2
        out.append((a,b,mask_poly(mk,res)))
    return out
if __name__=='__main__':
    from load import load_raw
    import trimesh
    from geom import section
    n=sys.argv[1]; V,F,P=load_raw(n)
    import time; t=time.time()
    rb=raster_bands(V,F)
    print('t',time.time()-t)
    m=trimesh.Trimesh(V,F,process=True)
    for a,b,p in rb:
        s=section(m,(a+b)/2)
        print(f'{a:.2f}-{b:.2f} raster {p.area:.1f} section {s.area:.1f} xor {p.symmetric_difference(s).area:.1f}')
