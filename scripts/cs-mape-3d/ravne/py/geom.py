import sys,os; sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
import numpy as np, trimesh
from shapely.geometry import Polygon, MultiPolygon
from shapely.ops import unary_union
from load import load_raw
def section(m,z):
    s=m.section([0,0,1],[0,0,z])
    if s is None: return Polygon()
    p2,T=s.to_2D(to_2D=np.eye(4))
    res=Polygon()
    for p in sorted([q.buffer(0) for q in p2.polygons_closed if q is not None],key=lambda q:-q.area):
        res=res.symmetric_difference(p)
    return res.buffer(0)
def bands(m):
    zs=np.unique(np.round(m.vertices[:,2],3))
    out=[]
    for a,b in zip(zs[:-1],zs[1:]):
        out.append((a,b,section(m,(a+b)/2)))
    return out
if __name__=='__main__':
    n=sys.argv[1]
    V,F,P=load_raw(n)
    m=trimesh.Trimesh(V,F,process=True)
    for a,b,p in bands(m):
        print(f'{a:.3f}-{b:.3f} area {p.area:.1f} ngeoms {len(getattr(p,"geoms",[p]))}')
